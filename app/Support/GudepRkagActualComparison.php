<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class GudepRkagActualComparison
{
    /**
     * A category-level comparison, NOT a verified link between a budget
     * breakdown and individual cash transactions. Only exact normalized
     * category matches are considered; unmatched categories show zero and
     * are explicitly marked unmatched.
     *
     * @param  array<int, array<string, mixed>>  $programEntries
     * @param  array<int, array<string, mixed>>  $postedActuals
     * @return array<int, array{direction: string, category: string, planned: int, actual: int, variance: int, matched: bool}>
     */
    public static function compare(array $programEntries, array $postedActuals): array
    {
        $actualLookup = [];
        foreach ($postedActuals as $actual) {
            $direction = (string) ($actual['direction'] ?? '');
            $category = trim((string) ($actual['category'] ?? ''));
            if (! in_array($direction, ['income', 'expense'], true) || $category === '') {
                continue;
            }
            $key = $direction.'|'.Str::lower($category);
            $actualLookup[$key] = ($actualLookup[$key] ?? 0) + max(0, (int) ($actual['amount'] ?? 0));
        }

        $planning = [];
        foreach ($programEntries as $entry) {
            if (($entry['section'] ?? '') !== 'keuangan') {
                continue;
            }

            $direction = (string) ($entry['finance_type'] ?? '');
            $category = trim((string) ($entry['description'] ?? ''));
            if (! in_array($direction, ['income', 'expense'], true) || $category === '') {
                continue;
            }

            $key = $direction.'|'.Str::lower($category);
            if (! isset($planning[$key])) {
                $planning[$key] = [
                    'direction' => $direction,
                    'category' => $category,
                    'planned' => 0,
                    'actual' => 0,
                    'variance' => 0,
                    'matched' => false,
                ];
            }

            $planning[$key]['planned'] += max(0, (int) ($entry['amount'] ?? 0));
        }

        foreach ($planning as $key => &$row) {
            $row['matched'] = array_key_exists($key, $actualLookup);
            $row['actual'] = $actualLookup[$key] ?? 0;
            $row['variance'] = $row['planned'] - $row['actual'];
        }
        unset($row);

        ksort($planning);

        return array_values($planning);
    }
}
