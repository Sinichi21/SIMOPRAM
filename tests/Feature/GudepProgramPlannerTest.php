<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Administration\GudepProgramPlanner;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\GudepFormation;
use App\Models\GudepProgramSheet;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\ScoutLevel;
use App\Models\Semester;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GudepProgramPlannerTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{School, ScoutGroup, AcademicYear} */
    private function context(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $gudep = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Tanpa Penggalang',
        ]);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        return [$school, $gudep, $year];
    }

    public function test_annual_workbook_can_omit_missing_golongans_and_quantity(): void
    {
        [$school, $gudep, $year] = $this->context();

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('title', 'Program Tahunan 2026/2027')
            ->set('entries', [
                ['section' => 'bersama', 'description' => 'Hari Pramuka', 'quantity' => ''],
                ['section' => 'sarana', 'description' => 'Buku administrasi', 'quantity' => '2'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $sheet = GudepProgramSheet::query()->firstOrFail();
        $this->assertSame($school->id, $sheet->school_id);
        $this->assertSame($gudep->id, $sheet->scout_group_id);
        $this->assertSame($year->id, $sheet->academic_year_id);
        $this->assertSame('annual', $sheet->kind);
        $this->assertNull($sheet->entries[0]['quantity']);
        $this->assertSame(2, $sheet->entries[1]['quantity']);
        $this->assertCount(2, $sheet->entries);
    }

    public function test_semester_program_rejects_level_missing_from_gudep(): void
    {
        [$school, $gudep, $year] = $this->context();

        $semester = Semester::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'semester_number' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $level = ScoutLevel::query()->create([
            'code' => 'PENGGALANG',
            'name' => 'Penggalang',
            'sort_order' => 2,
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('kind', 'semester')
            ->set('semesterId', $semester->id)
            ->set('scoutLevelId', $level->id)
            ->set('title', 'Program Semester Penggalang')
            ->set('entries', [
                ['description' => 'Latihan Sandi', 'months' => [true, false, false, false, false, false], 'participants' => '', 'notes' => ''],
            ])
            ->call('save')
            ->assertHasErrors(['scoutLevelId']);

        $this->assertDatabaseCount('gudep_program_sheets', 0);
    }

    public function test_weekly_program_can_link_to_its_semester_without_copying_master_data(): void
    {
        [$school, $gudep, $year] = $this->context();

        $semester = Semester::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'semester_number' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $level = ScoutLevel::query()->create([
            'code' => 'SIAGA',
            'name' => 'Siaga',
            'sort_order' => 1,
        ]);
        GudepFormation::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'scout_level_id' => $level->id,
            'name' => 'Perindukan Siaga',
            'formation_type' => 'perindukan',
            'is_active' => true,
        ]);
        $parent = GudepProgramSheet::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'scout_level_id' => $level->id,
            'kind' => 'semester',
            'title' => 'Program Semester Siaga',
            'entries' => [['description' => 'Simpul', 'months' => [true, false, false, false, false, false], 'participants' => null, 'notes' => '']],
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('kind', 'weekly')
            ->set('semesterId', $semester->id)
            ->set('scoutLevelId', $level->id)
            ->set('parentId', $parent->id)
            ->set('scheduledFor', '2026-08-07')
            ->set('title', 'Latihan Minggu Pertama')
            ->set('entries', [['description' => 'Simpul hidup dan mati', 'sku' => 'SKU-01', 'skk' => '', 'notes' => '']])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gudep_program_sheets', [
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'kind' => 'weekly',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_weekly_program_links_existing_activity_without_copying_its_title(): void
    {
        [$school, $gudep, $year] = $this->context();

        $semester = Semester::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'semester_number' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $level = ScoutLevel::query()->create([
            'code' => 'SIAGA',
            'name' => 'Siaga',
            'sort_order' => 1,
        ]);
        GudepFormation::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'scout_level_id' => $level->id,
            'name' => 'Perindukan Siaga',
            'formation_type' => 'perindukan',
            'is_active' => true,
        ]);
        $activity = Activity::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'title' => 'Latihan Tali Temali',
            'start_at' => '2026-08-07 15:00:00',
            'end_at' => '2026-08-07 16:30:00',
            'status' => 'published',
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('kind', 'weekly')
            ->set('semesterId', $semester->id)
            ->set('scoutLevelId', $level->id)
            ->set('activityId', $activity->id)
            ->set('scheduledFor', '2026-08-07')
            ->set('title', 'Rencana Mingguan Pertama')
            ->set('entries', [['description' => 'Simpul', 'sku' => '1', 'skk' => '', 'notes' => '']])
            ->call('save')
            ->assertHasNoErrors();

        $sheet = GudepProgramSheet::query()->firstOrFail();
        $this->assertSame($activity->id, $sheet->activity_id);
        $this->assertSame('Rencana Mingguan Pertama', $sheet->title);
        $this->assertSame('Latihan Tali Temali', $sheet->activity->title);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_weekly_program_rejects_activity_from_another_academic_year(): void
    {
        [$school, $gudep, $year] = $this->context();

        $otherYear = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => false,
        ]);
        $activity = Activity::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $otherYear->id,
            'title' => 'Latihan Tahun Lalu',
            'start_at' => '2026-05-01 08:00:00',
            'end_at' => '2026-05-01 09:00:00',
            'status' => 'completed',
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('kind', 'weekly')
            ->set('activityId', $activity->id)
            ->set('title', 'Latihan Mingguan')
            ->set('entries', [['description' => 'Materi', 'sku' => '', 'skk' => '', 'notes' => '']])
            ->call('save')
            ->assertHasErrors(['activityId']);

        $this->assertDatabaseCount('gudep_program_sheets', 0);
    }

    public function test_cross_gudep_edit_is_not_permitted(): void
    {
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Kedua',
        ]);
        $sheet = GudepProgramSheet::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $other->id,
            'academic_year_id' => $year->id,
            'kind' => 'annual',
            'title' => 'Program Gudep Lain',
            'entries' => [['section' => 'bersama', 'description' => 'Kegiatan', 'quantity' => null]],
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('edit', $sheet->id)
            ->assertNotFound();
    }
}
