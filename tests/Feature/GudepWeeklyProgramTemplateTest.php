<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Administration\GudepProgramPlanner;
use App\Models\AcademicYear;
use App\Models\GudepFormation;
use App\Models\GudepOfficer;
use App\Models\GudepProgramSheet;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\ScoutUnitMember;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Support\GudepWeeklyPersonnel;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GudepWeeklyProgramTemplateTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{School, ScoutGroup, AcademicYear, Semester, ScoutLevel, GudepFormation, GudepOfficer, ScoutUnitMember} */
    private function setupPersonnel(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $gudep = ScoutGroup::query()->create([
            'school_id' => $school->id, 'name' => 'Gudep Pengujian',
            'male_number' => '04.191', 'female_number' => '04.192',
        ]);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);
        $semester = Semester::query()->create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Ganjil',
            'semester_number' => 1, 'start_date' => '2026-07-01',
            'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $level = ScoutLevel::query()->create([
            'code' => 'PENGGALANG', 'name' => 'Penggalang', 'sort_order' => 2,
        ]);
        $formation = GudepFormation::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'scout_level_id' => $level->id,
            'name' => 'Pasukan Penggalang Putri', 'formation_type' => 'pasukan',
            'gender' => 'putri', 'is_active' => true,
        ]);
        $pembina = GudepOfficer::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'gudep_formation_id' => $formation->id, 'scope' => 'satuan',
            'position' => 'Pembina Gugus Depan', 'person_name' => 'Pembina Contoh',
            'started_at' => '2026-07-01',
        ]);
        $unit = ScoutUnit::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'gudep_formation_id' => $formation->id, 'scout_level_id' => $level->id,
            'academic_year_id' => $year->id, 'name' => 'Regu Melati', 'unit_type' => 'regu',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'school_id' => $school->id, 'nis' => 'PG-1234',
            'name' => 'Pratama Contoh', 'status' => 'active',
        ]);
        $pratama = ScoutUnitMember::query()->create([
            'school_id' => $school->id, 'scout_unit_id' => $unit->id,
            'student_id' => $student->id, 'position' => 'Pratama',
            'joined_at' => '2026-07-01',
        ]);

        return [$school, $gudep, $year, $semester, $level, $formation, $pembina, $pratama];
    }

    public function test_weekly_program_saves_reference_signers_and_all_template_columns(): void
    {
        [$school, $gudep, $year, $semester, $level, $formation, $pembina, $pratama] = $this->setupPersonnel();

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)->set('academicYearId', $year->id)
            ->set('kind', 'weekly')
            ->set('semesterId', $semester->id)->set('scoutLevelId', $level->id)
            ->set('formationId', $formation->id)
            ->set('scheduledFor', '2026-08-07')->set('title', 'Program Latihan Penggalang')
            ->set('acknowledgedByRef', 'officer:'.$pembina->id)
            ->set('signedByRef', 'member:'.$pratama->id)
            ->set('entries', [[
                'time' => '15.00–15.15', 'description' => 'Pembukaan',
                'target' => 'Disiplin', 'sku' => 'SKU 2', 'skk' => 'SKK Ketangkasan',
                'equipment' => 'Bendera', 'officer' => 'Pembina',
                'interlude' => 'Tepuk Pramuka', 'notes' => 'Di lapangan',
            ]])
            ->call('save')->assertHasNoErrors();

        $sheet = GudepProgramSheet::query()->firstOrFail();
        $this->assertSame('officer:'.$pembina->id, $sheet->acknowledged_by_ref);
        $this->assertSame('member:'.$pratama->id, $sheet->signed_by_ref);
        $this->assertSame('15.00–15.15', $sheet->entries[0]['time']);
        $this->assertSame('Tepuk Pramuka', $sheet->entries[0]['interlude']);
        $this->assertDatabaseCount('activities', 0);
        $this->assertDatabaseCount('gudep_officers', 1);

        $this->get(route('administration.programs.print', $sheet->id))
            ->assertOk()->assertSee('A4 portrait')
            ->assertSee('PROGRAM LATIHAN MINGGUAN GUGUSDEPAN')
            ->assertSee('Pencapaian')->assertSee('Sasaran')
            ->assertSee('Acara Selingan')->assertSee('Pratama Contoh')
            ->assertSee('Pembina Contoh');
    }

    public function test_personnel_options_exclude_other_gudep_and_nonmatching_formation(): void
    {
        [$school, $gudep, $year, , $level, $formation, , $pratama] = $this->setupPersonnel();
        $other = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Lain']);
        GudepOfficer::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $other->id,
            'scope' => 'gudep', 'position' => 'Pembina Gudep',
            'person_name' => 'Pembina Luar', 'started_at' => '2026-07-01',
        ]);

        $options = GudepWeeklyPersonnel::candidates(
            $school->id, $gudep->id, $year->id, $formation->id, '2026-08-07'
        );
        $this->assertNotNull(GudepWeeklyPersonnel::find($options, 'member:'.$pratama->id));
        $this->assertNotContains('Pembina Luar', array_column($options, 'name'));
        $this->assertSame('Pratama', GudepWeeklyPersonnel::signerRole($level->name));
    }

    public function test_wrong_signer_role_is_rejected_without_modifying_membership(): void
    {
        [$school, $gudep, $year, $semester, $level, $formation, $pembina] = $this->setupPersonnel();

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)->set('academicYearId', $year->id)
            ->set('kind', 'weekly')->set('semesterId', $semester->id)
            ->set('scoutLevelId', $level->id)->set('formationId', $formation->id)
            ->set('scheduledFor', '2026-08-07')->set('title', 'Latihan')
            ->set('acknowledgedByRef', 'officer:'.$pembina->id)
            ->set('signedByRef', 'officer:'.$pembina->id)
            ->set('entries', [['description' => 'Simpul hidup', 'sku' => '', 'skk' => '', 'notes' => '']])
            ->call('save')->assertHasErrors(['signedByRef']);

        $this->assertDatabaseCount('gudep_program_sheets', 0);
    }

    public function test_legacy_weekly_draft_still_renders_when_new_columns_missing(): void
    {
        [$school, $gudep, $year, $semester, $level, $formation] = $this->setupPersonnel();
        $sheet = GudepProgramSheet::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'semester_id' => $semester->id,
            'scout_level_id' => $level->id, 'gudep_formation_id' => $formation->id,
            'letterhead_type' => 'female', 'kind' => 'weekly', 'title' => 'Draft Lama',
            'scheduled_for' => '2026-08-07',
            'entries' => [['description' => 'Simpul jangkar', 'sku' => 'SKU', 'skk' => '', 'notes' => '']],
        ]);

        $this->get(route('administration.programs.print', $sheet->id))
            ->assertOk()->assertSee('Simpul jangkar')
            ->assertSee('PROGRAM LATIHAN MINGGUAN GUGUSDEPAN')
            ->assertSee('A4 portrait');
    }
}
