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
     * Read-only reference from one completed/selected accounting year.
     * Posted expenses count positively and posted income reversals subtract.
     * No transactions or budgets are created, updated or automatically copied.
     *
     * @return array<int, array{category: string, actual_expense: int, posted_entries: int, reversals: int}>
     */
    public static function forYear(int $schoolId, int $gudepId, int $historicalYearId): array
    {
        abort_unless(ScoutGroup::query()->where('school_id', $schoolId)->whereKey($gudepId)->exists(), 404);
        abort_unless(AcademicYear::query()->where('school_id', $schoolId)->whereKey($historicalYearId)->exists(), 404);

        $entries = GudepCashEntry::query()
            ->where('school_id', $schoolId)
            ->where('scout_group_id', $gudepId)
            ->where('academic_year_id', $historicalYearId)
            ->where('status', 'posted')
            ->where(function ($query): void {
                $query->where(function ($expense): void {
                    $expense->where('direction', 'expense')->whereNull('reversal_of_id');
                })->orWhere(function ($reversal): void {
                    $reversal->where('direction', 'income')->whereNotNull('reversal_of_id');
                });
            })
            ->orderBy('id')
            ->get(['category', 'amount', 'direction', 'reversal_of_id']);

        $totals = [];
        foreach ($entries as $entry) {
            $category = trim((string) $entry->category);
            if ($category === '') {
                continue;
            }

            $key = Str::lower($category);
            if (! isset($totals[$key])) {
                $totals[$key] = [
                    'category' => $category,
                    'actual_expense' => 0,
                    'posted_entries' => 0,
                    'reversals' => 0,
                ];
            }

            if ($entry->reversal_of_id !== null) {
                $totals[$key]['actual_expense'] -= (int) $entry->amount;
                $totals[$key]['reversals']++;
            } else {
                $totals[$key]['actual_expense'] += (int) $entry->amount;
                $totals[$key]['posted_entries']++;
            }
        }

        // Treat categories with wholly reversed expenses as zero historical
        // realization; report the reversal count for inspection, never as a
        // negative suggested new budget.
        foreach ($totals as &$row) {
            $row['actual_expense'] = max(0, $row['actual_expense']);
        }
        unset($row);

        ksort($totals);

        return array_values($totals);
    }
}
