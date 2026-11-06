<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Administration\GudepProgramPlanner;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\GudepCashEntry;
use App\Models\GudepFormation;
use App\Models\GudepProgramSheet;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\ScoutLevel;
use App\Models\Semester;
use App\Models\User;
use App\Support\GudepAnnualProgram;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            'male_number' => '04.191',
            'female_number' => '04.192',
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

    public function test_program_print_view_formats_optional_counts_without_editing_master_data(): void
    {
        [$school, $gudep, $year] = $this->context();

        $sheet = GudepProgramSheet::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'kind' => 'annual',
            'title' => 'Program Tahunan Tanpa Penggalang',
            'entries' => [
                ['section' => 'siaga', 'description' => 'Latihan Sandi', 'quantity' => null],
                ['section' => 'bersama', 'description' => 'Hari Pramuka', 'quantity' => 2],
            ],
        ]);

        $this->get(route('administration.programs.print', $sheet))
            ->assertOk()
            ->assertSee('PROGRAM KERJA TAHUNAN')
            ->assertSee('Program Tahunan Tanpa Penggalang')
            ->assertSee('Hari Pramuka')
            ->assertSee('Latihan Sandi')
            ->assertSee('Cetak / Simpan sebagai PDF')
            ->assertSee('–');

        $this->assertDatabaseCount('gudep_program_sheets', 1);
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_program_print_does_not_expose_another_schools_sheet(): void
    {
        [$school, $gudep, $year] = $this->context();

        $otherSchool = School::factory()->create();
        app(SchoolContext::class)->set($otherSchool);
        $otherGudep = ScoutGroup::query()->create(['school_id' => $otherSchool->id, 'name' => 'Gudep Rahasia']);
        $otherYear = AcademicYear::query()->create([
            'school_id' => $otherSchool->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => false,
        ]);
        $sheet = GudepProgramSheet::query()->create([
            'school_id' => $otherSchool->id,
            'scout_group_id' => $otherGudep->id,
            'academic_year_id' => $otherYear->id,
            'kind' => 'annual',
            'title' => 'Program Gudep Rahasia',
            'entries' => [['section' => 'siaga', 'description' => 'Rencana', 'quantity' => 1]],
        ]);
        app(SchoolContext::class)->set($school);

        $this->get(route('administration.programs.print', $sheet))->assertNotFound();
    }

    public function test_official_program_pdf_reuses_unchanged_snapshot_and_revises_changed_content(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();
        $sheet = GudepProgramSheet::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'kind' => 'annual',
            'title' => 'Program Tahunan Gudep',
            'entries' => [
                ['section' => 'siaga', 'description' => 'Latihan Sandi', 'quantity' => null],
                ['section' => 'bersama', 'description' => 'Hari Pramuka', 'quantity' => 1],
            ],
        ]);

        $publish = fn () => Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('publishProgram', $sheet->id)
            ->assertHasNoErrors();

        $publish();
        $publish();

        $first = ReportVerification::query()
            ->where('document_type', 'gudep_program_sheet')->firstOrFail();

        $this->assertSame(GudepProgramSheet::class, $first->source_type);
        $this->assertSame($sheet->id, $first->source_id);
        $this->assertSame(1, $first->revision);
        $this->assertSame(64, strlen($first->file_sha256));
        $this->assertSame('program-'.$sheet->id, $first->series_key);
        $this->assertDatabaseCount('report_verifications', 1);
        Storage::disk('local')->assertExists($first->file_path);

        $sheet->update(['title' => 'Program Tahunan Gudep Revisi']);

        $publish();
        $latest = ReportVerification::query()
            ->where('document_type', 'gudep_program_sheet')
            ->orderByDesc('revision')->firstOrFail();

        $this->assertSame(2, $latest->revision);
        $this->assertSame($first->id, $latest->replaces_id);
        $this->assertSame($latest->id, $first->fresh()->superseded_by_id);
        $this->assertNotSame($first->file_sha256, $latest->file_sha256);
        Storage::disk('local')->assertExists($first->file_path);
        Storage::disk('local')->assertExists($latest->file_path);
    }

    public function test_official_program_pdf_rejects_cross_gudep_id_without_issuing_document(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();
        $foreignGudep = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Lain',
        ]);
        $foreignSheet = GudepProgramSheet::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $foreignGudep->id,
            'academic_year_id' => $year->id,
            'kind' => 'annual',
            'title' => 'Program Gudep Lain',
            'entries' => [['section' => 'bersama', 'description' => 'Latihan', 'quantity' => 1]],
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('publishProgram', $foreignSheet->id)
            ->assertNotFound();

        $this->assertDatabaseCount('report_verifications', 0);
    }

    public function test_annual_document_template_hides_unavailable_levels_and_keeps_nested_numbering(): void
    {
        [$school, $gudep, $year] = $this->context();
        $level = ScoutLevel::query()->firstOrCreate(['code' => 'siaga'], ['name' => 'Siaga']);
        GudepFormation::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'scout_level_id' => $level->id,
            'name' => 'Perindukan Siaga',
            'formation_type' => 'perindukan',
            'is_active' => true,
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('loadAnnualTemplate')
            ->set('title', 'Program Kerja Tahunan 2026-2027')
            ->call('save')
            ->assertHasNoErrors();

        $sheet = GudepProgramSheet::query()->firstOrFail();
        $this->assertNotEmpty(GudepAnnualProgram::numbered($sheet->entries, 'siaga'));
        $this->assertSame([], GudepAnnualProgram::numbered($sheet->entries, 'penggalang'));
        $this->assertSame('1)', GudepAnnualProgram::numbered($sheet->entries, 'siaga')[1]['marker']);

        $this->get(route('administration.programs.print', $sheet->id))
            ->assertOk()
            ->assertSee('PROGRAM KERJA TAHUNAN')
            ->assertSee('TAHUN 2026-2027')
            ->assertSee('Pencapaian SKU')
            ->assertSee('Mula')
            ->assertSee('Rencana Pemasukan')
            ->assertSee('Jumlah rencana pengeluaran')
            ->assertDontSee('2. Penggalang');
    }

    public function test_annual_finance_is_a_plan_and_does_not_post_cash_entries(): void
    {
        [$school, $gudep, $year] = $this->context();
        $entries = GudepAnnualProgram::template([]);
        foreach ($entries as &$entry) {
            if ($entry['description'] === 'Iuran anggota') {
                $entry['amount'] = 1000000;
            } elseif ($entry['description'] === 'Biaya kegiatan Gugusdepan') {
                $entry['amount'] = 1500000;
            }
        }
        unset($entry);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('title', 'Program Gudep Dengan Rencana Anggaran')
            ->set('entries', $entries)
            ->call('save')
            ->assertHasNoErrors();

        $sheet = GudepProgramSheet::query()->firstOrFail();
        $this->assertSame(500000, GudepAnnualProgram::totals($sheet->entries)['shortfall']);
        $this->assertDatabaseCount('gudep_cash_entries', 0);
        $this->assertDatabaseCount('gudep_finance_budgets', 0);
    }

    public function test_historical_cash_suggestion_is_net_of_reversal_and_only_updates_planned_amount(): void
    {
        [$school, $gudep, $year] = $this->context();
        $previous = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => false,
        ]);
        $original = GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $previous->id,
            'direction' => 'income',
            'category' => 'Iuran anggota',
            'description' => 'Penerimaan iuran',
            'occurred_at' => '2026-01-15',
            'amount' => 500000,
            'status' => 'posted',
        ]);
        GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $previous->id,
            'direction' => 'expense',
            'category' => 'Iuran anggota',
            'description' => 'Pembalikan sebagian iuran',
            'occurred_at' => '2026-01-16',
            'amount' => 100000,
            'status' => 'posted',
            'reversal_of_id' => $original->id,
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('entries', [[
                'key' => 'income-line', 'parent_key' => null, 'section' => 'keuangan',
                'description' => 'Iuran anggota', 'finance_type' => 'income',
                'currency' => 'IDR', 'amount' => null,
            ]])
            ->call('useBudgetSuggestion', 0, 0)
            ->assertHasNoErrors()
            ->assertSet('entries.0.amount', 400000);

        $this->assertDatabaseCount('gudep_cash_entries', 2);
        $this->assertDatabaseCount('gudep_program_sheets', 0);
    }

    public function test_annual_program_always_renders_a4_mabigus_letterhead(): void
    {
        [$school, $gudep, $year] = $this->context();
        $sheet = GudepProgramSheet::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'kind' => 'annual',
            'letterhead_type' => 'mabigus',
            'title' => 'Program Tahunan',
            'entries' => [['section' => 'bersama', 'description' => 'Hari Pramuka', 'quantity' => null]],
        ]);

        $this->get(route('administration.programs.print', $sheet->id))
            ->assertOk()
            ->assertSee('A4 portrait')
            ->assertSee('MAJELIS PEMBIMBING GUGUSDEPAN')
            ->assertSee('04.191 - 04.192')
            ->assertDontSee('A5 portrait');
    }

    public function test_female_unit_auto_selects_female_letterhead_and_persists_formation(): void
    {
        [$school, $gudep, $year] = $this->context();
        $semester = Semester::query()->create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Ganjil', 'semester_number' => 1,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $level = ScoutLevel::query()->firstOrCreate(['code' => 'siaga'], ['name' => 'Siaga']);
        $formation = GudepFormation::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'scout_level_id' => $level->id,
            'name' => 'Perindukan Siaga Putri', 'formation_type' => 'perindukan',
            'gender' => 'putri', 'is_active' => true,
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('kind', 'semester')
            ->set('semesterId', $semester->id)
            ->set('scoutLevelId', $level->id)
            ->assertSet('letterheadType', 'female')
            ->assertSet('formationId', $formation->id)
            ->set('title', 'Program Semester Siaga Putri')
            ->set('entries', [['description' => 'Latihan Siaga', 'months' => [true, false, false, false, false, false], 'participants' => null, 'notes' => '']])
            ->call('save')
            ->assertHasNoErrors();

        $sheet = GudepProgramSheet::query()->firstOrFail();
        $this->assertSame('female', $sheet->letterhead_type);
        $this->assertSame($formation->id, $sheet->gudep_formation_id);

        $this->get(route('administration.programs.print', $sheet->id))
            ->assertOk()->assertSee('GUGUSDEPAN PUTRI')
            ->assertSee('04.192')->assertDontSee('A5 portrait');
    }

    public function test_mismatched_unit_letterhead_is_rejected_without_saving(): void
    {
        [$school, $gudep, $year] = $this->context();
        $semester = Semester::query()->create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Ganjil', 'semester_number' => 1,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $level = ScoutLevel::query()->firstOrCreate(['code' => 'siaga'], ['name' => 'Siaga']);
        $formation = GudepFormation::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'scout_level_id' => $level->id,
            'name' => 'Perindukan Siaga Putra', 'formation_type' => 'perindukan',
            'gender' => 'putra', 'is_active' => true,
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('kind', 'semester')
            ->set('semesterId', $semester->id)
            ->set('scoutLevelId', $level->id)
            ->set('formationId', $formation->id)
            ->set('letterheadType', 'female')
            ->set('title', 'Program Salah Kop')
            ->set('entries', [['description' => 'Latihan', 'months' => [true, false, false, false, false, false], 'participants' => null, 'notes' => '']])
            ->call('save')
            ->assertHasErrors(['letterheadType']);

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
