<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GudepProgramSheet;
use App\Models\GudepRkagDecision;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\GudepRkagApproval;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GudepRkagApprovalTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{GudepProgramSheet, User, User} */
    private function context(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $author = User::factory()->create(['system_role' => 'super_admin']);
        $reviewer = User::factory()->create(['system_role' => 'super_admin']);

        $gudep = ScoutGroup::query()->create(['name' => 'Gudep Pengujian']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $sheet = GudepProgramSheet::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'kind' => 'annual',
            'letterhead_type' => 'male', 'title' => 'RKAG Persami',
            'entries' => [[
                'key' => 'persami', 'section' => 'keuangan',
                'description' => 'Persami', 'finance_type' => 'expense',
                'amount' => 375000, 'rkag_lines' => [
                    ['description' => 'Konsumsi', 'volume' => 25, 'unit' => 'orang', 'unit_price' => 15000, 'subtotal' => 375000],
                ],
            ]],
        ]);

        return [$sheet, $author, $reviewer];
    }

    public function test_submission_and_approval_have_separate_actors_and_append_only_audit(): void
    {
        [$sheet, $author, $reviewer] = $this->context();
        $this->assertSame('draft', GudepRkagApproval::state($sheet)['status']);
        $this->assertDatabaseCount('gudep_rkag_decisions', 0);

        GudepRkagApproval::transition($sheet, $author, 'submit', 'Mohon persetujuan');
        $this->assertSame('submitted', GudepRkagApproval::state($sheet)['status']);

        try {
            GudepRkagApproval::transition($sheet, $author, 'approve');
            $this->fail('The submitting user must not approve their own request.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('gudep_rkag_decisions', 1);
        }

        GudepRkagApproval::transition($sheet, $reviewer, 'approve', 'Disetujui');
        $this->assertSame('approved', GudepRkagApproval::state($sheet)['status']);
        $this->assertDatabaseCount('gudep_rkag_decisions', 2);
        $this->assertSame(['submit', 'approve'], GudepRkagDecision::query()->orderBy('id')->pluck('action')->all());
        $this->assertSame(375000, $sheet->fresh()->entries[0]['amount']);
        $this->assertDatabaseCount('gudep_cash_entries', 0);
        $this->assertDatabaseCount('gudep_finance_budgets', 0);
    }

    public function test_revising_finance_after_approval_invalidates_snapshot_and_requires_resubmission(): void
    {
        [$sheet, $author, $reviewer] = $this->context();
        GudepRkagApproval::transition($sheet, $author, 'submit');
        GudepRkagApproval::transition($sheet, $reviewer, 'approve');

        $entries = $sheet->entries;
        $entries[0]['amount'] = 480000;
        $sheet->update(['entries' => $entries]);

        $this->assertSame('stale', GudepRkagApproval::state($sheet->fresh())['status']);

        try {
            GudepRkagApproval::transition($sheet, $reviewer, 'approve');
            $this->fail('Review cannot approve a stale submission.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('gudep_rkag_decisions', 2);
        }

        GudepRkagApproval::transition($sheet, $author, 'submit', 'Revisi volume');
        $this->assertSame('submitted', GudepRkagApproval::state($sheet->fresh())['status']);
        GudepRkagApproval::transition($sheet, $reviewer, 'reject', 'Pagu perlu disesuaikan');
        $this->assertSame('rejected', GudepRkagApproval::state($sheet->fresh())['status']);
        $this->assertDatabaseCount('gudep_rkag_decisions', 4);
    }

    public function test_nonannual_document_cannot_be_submitted(): void
    {
        [$sheet, $author] = $this->context();
        $sheet->update(['kind' => 'weekly']);

        $this->expectException(HttpException::class);
        GudepRkagApproval::transition($sheet, $author, 'submit');
    }

    public function test_rejection_requires_reason_and_does_not_replace_submitted_event(): void
    {
        [$sheet, $author, $reviewer] = $this->context();
        GudepRkagApproval::transition($sheet, $author, 'submit');

        try {
            GudepRkagApproval::transition($sheet, $reviewer, 'reject', '  ');
            $this->fail('Rejection without a reason must not be recorded.');
        } catch (ValidationException) {
            $this->assertSame('submitted', GudepRkagApproval::state($sheet)['status']);
            $this->assertDatabaseCount('gudep_rkag_decisions', 1);
        }

        GudepRkagApproval::transition($sheet, $reviewer, 'reject', 'Rincian perlu diperbaiki');
        $this->assertDatabaseCount('gudep_rkag_decisions', 2);
        $this->assertSame('Rincian perlu diperbaiki', GudepRkagDecision::query()->latest('id')->firstOrFail()->notes);
    }

    public function test_active_school_context_must_match_document_school(): void
    {
        [$sheet, $author] = $this->context();
        $otherSchool = School::factory()->create();
        app(SchoolContext::class)->set($otherSchool);
        setPermissionsTeamId($otherSchool->id);

        $this->expectException(HttpException::class);
        GudepRkagApproval::transition($sheet, $author, 'submit');
    }

    public function test_nonfinancial_changes_do_not_invalidate_finance_approval(): void
    {
        [$sheet, $author, $reviewer] = $this->context();
        GudepRkagApproval::transition($sheet, $author, 'submit');
        GudepRkagApproval::transition($sheet, $reviewer, 'approve');

        $sheet->update(['title' => 'Perbaikan Judul Program']);
        $this->assertSame('approved', GudepRkagApproval::state($sheet->fresh())['status']);
    }
}
