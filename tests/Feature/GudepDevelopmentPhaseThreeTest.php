<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepDevelopment;
use App\Models\Activity;
use App\Models\AcademicYear;
use App\Models\GudepAchievement;
use App\Models\GudepCompetencyRecord;
use App\Models\GudepWorkProgram;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\ScoutUnitMember;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GudepDevelopmentPhaseThreeTest extends TestCase
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
            'name' => 'Gudep Bina Pramuka',
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

    /** @return array{Student, ScoutUnit} */
    private function member(School $school, ScoutGroup $gudep, AcademicYear $year): array
    {
        $level = ScoutLevel::query()->firstOrCreate(
            ['code' => 'PENGGALANG'],
            ['name' => 'Penggalang', 'sort_order' => 2]
        );
        $unit = ScoutUnit::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'scout_level_id' => $level->id,
            'academic_year_id' => $year->id,
            'name' => 'Regu Cendrawasih',
            'unit_type' => 'regu',
        ]);
        $student = Student::query()->create([
            'school_id' => $school->id,
            'nta' => 'NTA-TEST-001',
            'nis' => 'NIS-TEST-001',
            'name' => 'Anggota Penggalang',
            'status' => 'active',
        ]);
        ScoutUnitMember::query()->create([
            'school_id' => $school->id,
            'scout_unit_id' => $unit->id,
            'student_id' => $student->id,
            'joined_at' => '2026-07-01',
        ]);

        return [$student, $unit];
    }

    public function test_development_screen_requires_authentication(): void
    {
        $this->get(route('administration.development'))->assertRedirect();
    }

    public function test_program_is_scoped_to_school_gudep_and_year(): void
    {
        [$school, $gudep, $year] = $this->context();

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('programTitle', 'Latihan Rutin Penggalang')
            ->set('programObjective', 'Pencapaian kompetensi peserta')
            ->set('programDate', '2026-09-01')
            ->call('saveProgram')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gudep_work_programs', [
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'title' => 'Latihan Rutin Penggalang',
            'status' => 'planned',
        ]);
    }

    public function test_another_gudep_cannot_change_program_status(): void
    {
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Lain',
        ]);

        $program = GudepWorkProgram::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $other->id,
            'academic_year_id' => $year->id,
            'title' => 'Program Gudep Lain',
            'status' => 'planned',
        ]);

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->call('setProgramStatus', $program->id, 'completed')
            ->assertNotFound();

        $this->assertSame('planned', $program->fresh()->status);
    }

    public function test_sku_progress_can_be_updated_without_duplicate_records(): void
    {
        [$school, $gudep, $year] = $this->context();
        [$student] = $this->member($school, $gudep, $year);

        $form = fn (string $status) => Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('studentId', $student->id)
            ->set('competencyCategory', 'SKU')
            ->set('requirementCode', 'SKU-01')
            ->set('requirementName', 'Ketentuan dasar')
            ->set('competencyStatus', $status)
            ->call('saveCompetency')
            ->assertHasNoErrors();

        $form('in_progress');
        $form('passed');

        $this->assertSame(1, GudepCompetencyRecord::query()->count());
        $record = GudepCompetencyRecord::query()->firstOrFail();
        $this->assertSame('passed', $record->status);
        $this->assertSame($year->id, $record->academic_year_id);
        $this->assertNotNull($record->assessed_at);
    }

    public function test_competency_rejects_member_of_another_gudep(): void
    {
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create([
            'school_id' => $school->id, 'name' => 'Gudep Kedua',
        ]);
        [$student] = $this->member($school, $other, $year);

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('studentId', $student->id)
            ->set('competencyCategory', 'SKK')
            ->set('requirementCode', 'SKK-1')
            ->set('requirementName', 'Uji kecakapan khusus')
            ->call('saveCompetency')
            ->assertHasErrors(['studentId']);

        $this->assertDatabaseCount('gudep_competency_records', 0);
    }

    public function test_tkk_award_is_recorded_separately_from_skk_examination(): void
    {
        [$school, $gudep, $year] = $this->context();
        [$student] = $this->member($school, $gudep, $year);

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('achievementStudentId', $student->id)
            ->set('achievementKind', 'tkk_award')
            ->set('achievementTitle', 'TKK Pertolongan Pertama')
            ->set('awardedAt', '2026-10-01')
            ->call('saveAchievement')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gudep_achievements', [
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'student_id' => $student->id,
            'kind' => 'tkk_award',
        ]);
        $this->assertSame(0, GudepCompetencyRecord::query()->count());
    }

    public function test_annual_portfolio_is_archived_with_a_qr_and_reuses_unchanged_snapshot(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();

        $component = fn () => Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('publishPortfolio')
            ->assertHasNoErrors();

        $component();
        $component();

        $documents = ReportVerification::query()
            ->where('school_id', $school->id)
            ->where('document_type', 'gudep_development')
            ->where('series_key', 'year-'.$year->id)->get();

        $this->assertCount(1, $documents);
        $document = $documents->first();
        $this->assertSame(1, $document->revision);
        $this->assertTrue($document->hasArchivedPdf());
        $this->assertSame('Terbatas', data_get($document->metadata, 'security_classification'));
        Storage::disk('local')->assertExists($document->file_path);

        GudepWorkProgram::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'title' => 'Kegiatan Baru',
            'status' => 'planned',
        ]);
        $component();

        $this->assertSame(2, ReportVerification::query()
            ->where('document_type', 'gudep_development')->count());
        $this->assertSame('superseded', $document->fresh()->publicStatus());
    }

    public function test_old_academic_year_portfolio_does_not_replace_new_year(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();
        $old = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => false,
        ]);

        foreach ([$year, $old] as $academicYear) {
            Livewire::test(GudepDevelopment::class)
                ->set('scoutGroupId', $gudep->id)
                ->set('academicYearId', $academicYear->id)
                ->call('publishPortfolio')
                ->assertHasNoErrors();
        }

        $docs = ReportVerification::query()->where('document_type', 'gudep_development')
            ->orderBy('id')->get();
        $this->assertSame(2, $docs->count());
        $this->assertSame([1, 1], $docs->pluck('revision')->all());
        $this->assertSame(['valid', 'valid'], $docs->map->publicStatus()->all());
    }
    public function test_member_from_previous_year_is_not_eligible_for_new_year_competency(): void
    {
        [$school, $gudep, $currentYear] = $this->context();
        $old = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => false,
        ]);
        [$student] = $this->member($school, $gudep, $old);

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $currentYear->id)
            ->set('studentId', $student->id)
            ->set('requirementCode', 'SKU-01')
            ->set('requirementName', 'Syarat Kecakapan Umum')
            ->call('saveCompetency')
            ->assertHasErrors(['studentId']);

        $this->assertDatabaseCount('gudep_competency_records', 0);
    }

    public function test_tkk_cannot_be_recorded_without_an_eligible_member(): void
    {
        [, $gudep, $year] = $this->context();

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('achievementKind', 'tkk_award')
            ->set('achievementTitle', 'TKK Pertolongan Pertama')
            ->set('awardedAt', '2026-10-01')
            ->call('saveAchievement')
            ->assertHasErrors(['achievementStudentId']);

        $this->assertDatabaseCount('gudep_achievements', 0);
    }

    public function test_coach_development_requires_an_existing_coach(): void
    {
        [, $gudep, $year] = $this->context();

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('achievementKind', 'development')
            ->set('achievementTitle', 'Kursus Pembina')
            ->set('awardedAt', '2026-10-01')
            ->call('saveAchievement')
            ->assertHasErrors(['achievementCoachId']);

        $this->assertSame(0, GudepAchievement::query()->count());
    }

    public function test_program_cannot_link_an_activity_from_a_different_academic_year(): void
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

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('programTitle', 'Program Baru')
            ->set('programActivityId', $activity->id)
            ->call('saveProgram')
            ->assertHasErrors(['programActivityId']);

        $this->assertDatabaseCount('gudep_work_programs', 0);
    }

}
