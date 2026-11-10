<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Administration\GudepProgramPlanner;
use App\Models\AcademicYear;
use App\Models\GudepOfficer;
use App\Models\GudepProgramSheet;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GudepProgramPublicationRevisionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{School, ScoutGroup, AcademicYear, GudepOfficer, GudepOfficer} */
    private function setupContext(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $gudep = ScoutGroup::query()->create([
            'school_id' => $school->id, 'name' => 'Gudep Dokumen',
            'male_number' => '04.191', 'female_number' => '04.192',
        ]);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);
        $kamabigus = GudepOfficer::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'scope' => 'mabi', 'position' => 'Ketua Mabigus',
            'person_name' => 'Ketua Mabigus Pengujian', 'started_at' => '2026-07-01',
        ]);
        $pembina = GudepOfficer::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'scope' => 'gudep', 'position' => 'Pembina Gugusdepan',
            'person_name' => 'Pembina Gudep Pengujian', 'started_at' => '2026-07-01',
        ]);

        return [$school, $gudep, $year, $kamabigus, $pembina];
    }

    public function test_annual_putri_plan_saves_alternative_matrix_tembusan_and_real_signers(): void
    {
        [, $gudep, $year, $kamabigus, $pembina] = $this->setupContext();
        $months = array_fill(0, 12, false);
        $months[0] = true;
        $months[4] = true;

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)->set('academicYearId', $year->id)
            ->set('kind', 'annual')->set('letterheadType', 'female')
            ->set('documentLayout', 'monthly_matrix')
            ->set('title', 'Program Tahunan Gudep Putri')
            ->set('acknowledgedByRef', 'officer:'.$kamabigus->id)
            ->set('signedByRef', 'officer:'.$pembina->id)
            ->set('ccRecipients', ['Ketua Kwartir Ranting', 'Arsip Gugusdepan'])
            ->set('entries', [[
                'key' => 'sku-01', 'parent_key' => null, 'section' => 'penggalang',
                'description' => 'Latihan Penggalang Ramu',
                'months' => $months, 'quantity' => 2, 'unit' => 'kali',
                'detail' => '', 'finance_type' => '', 'amount' => null,
            ]])
            ->call('save')->assertHasNoErrors();

        $sheet = GudepProgramSheet::query()->firstOrFail();
        $this->assertSame('female', $sheet->letterhead_type);
        $this->assertSame('monthly_matrix', $sheet->document_layout);
        $this->assertSame('officer:'.$kamabigus->id, $sheet->acknowledged_by_ref);
        $this->assertSame('officer:'.$pembina->id, $sheet->signed_by_ref);
        $this->assertSame(['Ketua Kwartir Ranting', 'Arsip Gugusdepan'], $sheet->cc_recipients);
        $this->assertSame($months, $sheet->entries[0]['months']);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)->set('academicYearId', $year->id)
            ->call('edit', $sheet->id)
            ->assertSet('documentLayout', 'monthly_matrix')
            ->assertSet('letterheadType', 'female')
            ->assertSet('entries.0.months.0', true);
    }

    public function test_annual_publication_requires_both_signatures_and_archives_pdf_with_tembusan(): void
    {
        Storage::fake('local');
        [, $gudep, $year, $kamabigus, $pembina] = $this->setupContext();
        $sheet = GudepProgramSheet::query()->create([
            'school_id' => $gudep->school_id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'kind' => 'annual',
            'letterhead_type' => 'female', 'document_layout' => 'monthly_matrix',
            'title' => 'Program Gudep Putri', 'cc_recipients' => ['Kwartir Ranting'],
            'entries' => [[
                'key' => 'a', 'section' => 'bersama',
                'description' => 'Bakti Masyarakat', 'months' => array_fill(0, 12, true),
            ]],
        ]);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)->set('academicYearId', $year->id)
            ->call('publishProgram', $sheet->id)
            ->assertHasErrors(['publication']);
        $this->assertDatabaseCount('report_verifications', 0);

        $sheet->update([
            'acknowledged_by_ref' => 'officer:'.$kamabigus->id,
            'signed_by_ref' => 'officer:'.$pembina->id,
        ]);
        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)->set('academicYearId', $year->id)
            ->call('publishProgram', $sheet->id)->assertHasNoErrors();

        $document = ReportVerification::query()
            ->where('document_type', 'gudep_program_sheet')->firstOrFail();
        $this->assertTrue($document->hasArchivedPdf());
        Storage::disk('local')->assertExists($document->file_path);
        $bytes = Storage::disk('local')->get($document->file_path);
        $this->assertStringStartsWith('%PDF-', $bytes);
        // A4 landscape has a width of 841.89pt and height of 595.28pt.
        $this->assertMatchesRegularExpression(
            '/\\/MediaBox\\s*\\[\\s*0(?:\\.0+)?\\s+0(?:\\.0+)?\\s+84[01](?:\\.\\d+)?\\s+59[45](?:\\.\\d+)?\\s*\\]/',
            $bytes
        );

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)->set('academicYearId', $year->id)
            ->call('publishProgram', $sheet->id)->assertHasNoErrors();
        $this->assertDatabaseCount('report_verifications', 1);
    }

    public function test_more_than_four_tembusan_cannot_be_saved(): void
    {
        [, $gudep, $year] = $this->setupContext();

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)->set('academicYearId', $year->id)
            ->set('title', 'Program Pengujian')
            ->set('ccRecipients', ['A', 'B', 'C', 'D', 'E'])
            ->set('entries', [['section' => 'bersama', 'description' => 'Latihan', 'quantity' => null]])
            ->call('save')->assertHasErrors(['ccRecipients']);

        $this->assertDatabaseCount('gudep_program_sheets', 0);
    }
}
