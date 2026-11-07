<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\GudepProgramSheet;
use Illuminate\Support\Collection;

final class GudepProgramProgress
{
    /**
     * Read-only totals. Planning never creates an Activity, Journal or cash entry.
     *
     * @param  Collection<int, GudepProgramSheet>  $sheets
     * @return array{annual: int, semester: int, weekly: int, linked: int, completed: int, evidenced: int}
     */
    public static function summary(Collection $sheets): array
    {
        $weekly = $sheets->where('kind', 'weekly');

        return [
            'annual' => $sheets->where('kind', 'annual')->count(),
            'semester' => $sheets->where('kind', 'semester')->count(),
            'weekly' => $weekly->count(),
            'linked' => $weekly->filter(fn ($sheet): bool => $sheet->activity !== null)->count(),
            'completed' => $weekly->filter(fn ($sheet): bool => $sheet->activity?->status === 'completed')->count(),
            'evidenced' => $weekly->filter(fn ($sheet): bool => $sheet->activity?->status === 'completed'
                && $sheet->activity->journal !== null)->count(),
        ];
    }

    public static function evidenceLabel(GudepProgramSheet $sheet): string
    {
        if ($sheet->kind !== 'weekly') {
            return 'Rencana';
        }

        if ($sheet->activity === null) {
            return 'Belum ditautkan';
        }

        if ($sheet->activity->status !== 'completed') {
            return 'Agenda terhubung';
        }

        return $sheet->activity->journal === null
            ? 'Selesai · jurnal belum ada'
            : 'Selesai · jurnal tersedia';
    }
}
