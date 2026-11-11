<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GudepCashEntry;
use App\Models\GudepProgramSheet;
use App\Models\GudepRkagCashLink;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\GudepRkagApproval;
use App\Support\GudepRkagCashLinker;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GudepRkagCashLinkTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{GudepProgramSheet, GudepCashEntry, User, User} */
    private function context(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $author = User::factory()->create(['system_role' => 'super_admin']);
        $reviewer = User::factory()->create(['system_role' => 'super_admin']);
        $group = ScoutGroup::query()->create(['name' => 'Gudep Kas RKAG']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);
        $sheet = GudepProgramSheet::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $group->id,
            'academic_year_id' => $year->id, 'kind' => 'annual',
            'letterhead_type' => 'male', 'title' => 'RKAG',
            'entries' => [[
                'key' => 'persami', 'section' => 'keuangan',
                'description' => 'Persami', 'finance_type' => 'expense',
                'amount' => 375000, 'rkag_lines' => [[
                    'description' => 'Konsumsi', 'volume' => 25, 'unit' => 'orang',
                    'unit_price' => 15000, 'subtotal' => 375000,
                ]],
            ]],
        ]);
        $entry = GudepCashEntry::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $group->id,
            'academic_year_id' => $year->id, 'category' => 'Persami',
            'direction' => 'expense', 'amount' => 250000,
            'occurred_at' => '2026-11-10', 'status' => 'posted',
            'description' => 'Kas Konsumsi Persami',
        ]);

        return [$sheet, $entry, $author, $reviewer];
    }

    public function test_links_only_posted_cash_to_an_approved_budget_breakdown_once(): void
    {
        [$sheet, $entry, $author, $reviewer] = $this->context();
        GudepRkagApproval::transition($sheet, $author, 'submit');
        GudepRkagApproval::transition($sheet, $reviewer, 'approve');

        $link = GudepRkagCashLinker::link($sheet, $entry, $reviewer, 'persami', 0);
        $this->assertSame($sheet->id, $link->gudep_program_sheet_id);
        $this->assertSame($entry->id, $link->gudep_cash_entry_id);
        $this->assertSame('persami', $link->program_entry_key);
        $this->assertSame(64, strlen($link->line_fingerprint));
        $this->assertDatabaseCount('gudep_rkag_cash_links', 1);
        $this->assertSame(250000, $entry->fresh()->amount);
        $this->assertDatabaseCount('gudep_cash_entries', 1);
        $this->assertDatabaseCount('gudep_finance_budgets', 0);

        $this->expectException(ValidationException::class);
        GudepRkagCashLinker::link($sheet, $entry, $reviewer, 'persami', 0);
    }

    public function test_draft_plan_cannot_be_linked_to_a_cash_entry(): void
    {
        [$sheet, $entry, , $reviewer] = $this->context();

        $this->expectException(ValidationException::class);
        GudepRkagCashLinker::link($sheet, $entry, $reviewer, 'persami', 0);
    }

    public function test_wrong_direction_and_unposted_cash_are_rejected(): void
    {
        [$sheet, $entry, $author, $reviewer] = $this->context();
        GudepRkagApproval::transition($sheet, $author, 'submit');
        GudepRkagApproval::transition($sheet, $reviewer, 'approve');

        $entry->update(['direction' => 'income']);
        try {
            GudepRkagCashLinker::link($sheet, $entry, $reviewer, 'persami', 0);
            $this->fail('Income must not match an expense RKAG line.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('gudep_rkag_cash_links', 0);
        }

        $entry->update(['direction' => 'expense', 'status' => 'draft']);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        GudepRkagCashLinker::link($sheet, $entry, $reviewer, 'persami', 0);
    }

    public function test_revised_rkag_requires_new_approval_before_cash_link(): void
    {
        [$sheet, $entry, $author, $reviewer] = $this->context();
        GudepRkagApproval::transition($sheet, $author, 'submit');
        GudepRkagApproval::transition($sheet, $reviewer, 'approve');

        $rows = $sheet->entries;
        $rows[0]['rkag_lines'][0]['volume'] = 30;
        $sheet->update(['entries' => $rows]);

        $this->expectException(ValidationException::class);
        GudepRkagCashLinker::link($sheet, $entry, $reviewer, 'persami', 0);
    }
}
