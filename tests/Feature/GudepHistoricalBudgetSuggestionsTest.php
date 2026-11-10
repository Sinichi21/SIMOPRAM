<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GudepCashEntry;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Support\GudepHistoricalBudgetSuggestions;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GudepHistoricalBudgetSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_posted_entries_from_selected_school_gudep_and_year_are_suggested(): void
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $otherSchool = School::factory()->create();
        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Utama']);
        $anotherGudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Lain']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2025/2026',
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => false,
        ]);
        $otherYear = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);

        $record = function (int $gudepId, int $yearId, string $category, int $amount, string $status, string $direction = 'expense', ?int $reversalOf = null) use ($school): GudepCashEntry {
            return GudepCashEntry::query()->create([
                'school_id' => $school->id,
                'scout_group_id' => $gudepId,
                'academic_year_id' => $yearId,
                'category' => $category,
                'amount' => $amount,
                'direction' => $direction,
                'occurred_at' => '2026-03-10',
                'description' => 'Transaksi uji',
                'status' => $status,
                'reversal_of_id' => $reversalOf,
            ]);
        };

        $first = $record($gudep->id, $year->id, 'PERSAMI', 1500000, 'posted');
        $record($gudep->id, $year->id, 'Persami', 500000, 'posted');
        $record($gudep->id, $year->id, 'Persami', 2000000, 'draft');
        $record($gudep->id, $year->id, 'Persami', 250000, 'posted', 'income', $first->id);
        $record($gudep->id, $year->id, 'Iuran', 300000, 'posted', 'income');
        $record($anotherGudep->id, $year->id, 'Persami', 9000000, 'posted');
        $record($gudep->id, $otherYear->id, 'Persami', 8000000, 'posted');

        $before = GudepCashEntry::query()->count();
        $suggestions = GudepHistoricalBudgetSuggestions::forYear($school->id, $gudep->id, $year->id);

        $this->assertCount(2, $suggestions);
        $this->assertSame([
            'direction' => 'expense', 'category' => 'PERSAMI', 'amount' => 1750000,
            'source_year' => '2025/2026',
        ], $suggestions[0]);
        $this->assertSame([
            'direction' => 'income', 'category' => 'Iuran', 'amount' => 300000,
            'source_year' => '2025/2026',
        ], $suggestions[1]);
        $this->assertSame($before, GudepCashEntry::query()->count());
        $this->assertSame(0, GudepCashEntry::query()->where('academic_year_id', $otherYear->id)->where('status', 'draft')->count());

        $this->expectException(HttpException::class);
        GudepHistoricalBudgetSuggestions::forYear($otherSchool->id, $gudep->id, $year->id);
    }

    public function test_fully_reversed_expense_does_not_generate_negative_recommendation(): void
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Utama']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2025/2026',
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => false,
        ]);
        $original = GudepCashEntry::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'category' => 'Peralatan',
            'direction' => 'expense', 'amount' => 450000,
            'occurred_at' => '2026-02-01', 'description' => 'Beli',
            'status' => 'posted',
        ]);
        GudepCashEntry::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'category' => 'Peralatan',
            'direction' => 'income', 'amount' => 450000,
            'occurred_at' => '2026-02-02', 'description' => 'Koreksi',
            'status' => 'posted', 'reversal_of_id' => $original->id,
        ]);

        $this->assertSame([], GudepHistoricalBudgetSuggestions::forYear($school->id, $gudep->id, $year->id));
    }
}
