<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Semester;

final class GudepProgramSemesterProjection
{
    /** @return array<int, int> */
    public static function months(?Semester $semester): array
    {
        return (int) $semester?->semester_number === 2
            ? [1, 2, 3, 4, 5, 6, 7]
            : [8, 9, 10, 11, 12];
    }

    /** @return array<int, string> */
    public static function monthLabels(?Semester $semester): array
    {
        return array_map(static fn (int $month): string => [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ][$month], self::months($semester));
    }

    /**
     * Annual month slots are August through July. Semester slots are
     * August–December (five) or January–July (seven), not two equal halves.
     *
     * @param  array<int, mixed>  $annualMonths
     * @return array<int, bool>
     */
    public static function fromAnnual(array $annualMonths, ?Semester $semester): array
    {
        $annualSequence = [8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6, 7];

        return array_map(
            static fn (int $month): bool => (bool) ($annualMonths[array_search($month, $annualSequence, true)] ?? false),
            self::months($semester)
        );
    }

    /**
     * Resolve source rows through a stable source key, preserving notes and
     * participant counts on the semester draft when the annual plan changes.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, array<string, mixed>>  $annualEntries
     * @return array<string, mixed>|null
     */
    public static function synchronizeRow(array $row, array $annualEntries, ?Semester $semester): ?array
    {
        $key = (string) ($row['source_key'] ?? '');
        if ($key === '') {
            return $row;
        }

        foreach ($annualEntries as $index => $annual) {
            if ((string) ($annual['key'] ?? 'legacy-'.$index) !== $key) {
                continue;
            }

            if (($annual['section'] ?? '') === 'keuangan') {
                return null;
            }

            $row['description'] = (string) ($annual['description'] ?? '');
            $row['months'] = self::fromAnnual($annual['months'] ?? [], $semester);

            return $row;
        }

        return null;
    }
}
