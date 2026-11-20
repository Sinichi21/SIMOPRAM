<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\GudepCashEntry;
use App\Models\ScoutGroup;
use Illuminate\Support\Str;

final class GudepHistoricalBudgetSuggestions
{
    /**
     * Read-only previous-year suggestions using posted ledger entries only.
     * Each reversal reduces the corresponding original transaction direction.
     * No draft/production record is changed by generating a suggestion.
     *
     * @return array<int, array{direction: string, category: string, amount: int, source_year: string}>
     */
    public static function forYear(int $schoolId, int $gudepId, int $historicalYearId): array
    {
        abort_unless(ScoutGroup::query()->where('school_id', $schoolId)->whereKey($gudepId)->exists(), 404);
        $year = AcademicYear::query()->where('school_id', $schoolId)->findOrFail($historicalYearId);

        $entries = GudepCashEntry::query()
            ->where('school_id', $schoolId)
            ->where('scout_group_id', $gudepId)
            ->where('academic_year_id', $historicalYearId)
            ->where('status', 'posted')
            ->orderBy('id')
            ->get(['category', 'amount', 'direction', 'reversal_of_id']);

        $totals = [];
        foreach ($entries as $entry) {
            $category = trim((string) $entry->category);
            if ($category === '' || ! in_array($entry->direction, ['income', 'expense'], true)) {
                continue;
            }

            // A reversed expense is posted as income and should subtract
            // from historical expenses, not become new operating income.
            $direction = $entry->reversal_of_id !== null
                ? ($entry->direction === 'income' ? 'expense' : 'income')
                : $entry->direction;
            $key = $direction.'|'.Str::lower($category);
            if (! isset($totals[$key])) {
                $totals[$key] = [
                    'direction' => $direction,
                    'category' => $category,
                    'amount' => 0,
                    'source_year' => (string) $year->name,
                ];
            }

            $totals[$key]['amount'] += ($entry->reversal_of_id !== null ? -1 : 1) * (int) $entry->amount;
        }

        ksort($totals);

        return array_values(array_filter(
            $totals,
            static fn (array $row): bool => $row['amount'] > 0
        ));
    }
}
