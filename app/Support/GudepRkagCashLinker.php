<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\GudepCashEntry;
use App\Models\GudepProgramSheet;
use App\Models\GudepRkagCashLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GudepRkagCashLinker
{
    public static function lineFingerprint(array $line): string
    {
        return hash('sha256', json_encode([
            'description' => $line['description'] ?? '',
            'volume' => $line['volume'] ?? null,
            'unit' => $line['unit'] ?? '',
            'unit_price' => $line['unit_price'] ?? null,
            'funding_source' => $line['funding_source'] ?? '',
            'ceiling' => $line['ceiling'] ?? null,
            'subtotal' => $line['subtotal'] ?? null,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Explicitly associates one already posted cash transaction with one
     * approved RKAG breakdown. Neither source document is modified.
     */
    public static function link(
        GudepProgramSheet $sheet, GudepCashEntry $entry, User $actor,
        string $programEntryKey, int $lineIndex
    ): GudepRkagCashLink {
        abort_unless($actor->can('gudep.finance.manage'), 403);
        abort_unless((int) app(SchoolContext::class)->id() === (int) $sheet->school_id, 403);

        return DB::transaction(static function () use ($sheet, $entry, $actor, $programEntryKey, $lineIndex): GudepRkagCashLink {
            $lockedSheet = GudepProgramSheet::query()
                ->where('school_id', $sheet->school_id)
                ->where('scout_group_id', $sheet->scout_group_id)
                ->where('academic_year_id', $sheet->academic_year_id)
                ->where('kind', 'annual')
                ->lockForUpdate()->findOrFail($sheet->id);

            $posted = GudepCashEntry::query()
                ->where('school_id', $lockedSheet->school_id)
                ->where('scout_group_id', $lockedSheet->scout_group_id)
                ->where('academic_year_id', $lockedSheet->academic_year_id)
                ->where('status', 'posted')
                ->whereNull('reversal_of_id')
                ->lockForUpdate()->findOrFail($entry->id);

            $approval = GudepRkagApproval::state($lockedSheet);
            if ($approval['status'] !== 'approved') {
                throw ValidationException::withMessages([
                    'rkagLink' => 'Rincian RKAG harus sudah disetujui dan tidak berubah sejak persetujuan.',
                ]);
            }

            if (GudepRkagCashLink::query()->where('school_id', $lockedSheet->school_id)
                ->where('gudep_cash_entry_id', $posted->id)->exists()) {
                throw ValidationException::withMessages([
                    'rkagLink' => 'Transaksi kas ini sudah ditautkan ke rincian RKAG.',
                ]);
            }

            $target = null;
            foreach ($lockedSheet->entries ?? [] as $row) {
                if (($row['key'] ?? null) === $programEntryKey && ($row['section'] ?? '') === 'keuangan') {
                    $target = $row;
                    break;
                }
            }

            if ($target === null
                || ! in_array($target['finance_type'] ?? '', ['income', 'expense'], true)
                || ($target['finance_type'] ?? '') !== $posted->direction
                || ! isset($target['rkag_lines'][$lineIndex])) {
                throw ValidationException::withMessages([
                    'rkagLink' => 'Transaksi tidak cocok dengan jenis atau rincian RKAG yang dipilih.',
                ]);
            }

            return GudepRkagCashLink::query()->create([
                'school_id' => $lockedSheet->school_id,
                'scout_group_id' => $lockedSheet->scout_group_id,
                'academic_year_id' => $lockedSheet->academic_year_id,
                'gudep_program_sheet_id' => $lockedSheet->id,
                'gudep_cash_entry_id' => $posted->id,
                'linked_by' => $actor->id,
                'program_entry_key' => $programEntryKey,
                'rkag_line_index' => $lineIndex,
                'approved_snapshot_hash' => $approval['last']->snapshot_hash,
                'line_fingerprint' => self::lineFingerprint($target['rkag_lines'][$lineIndex]),
            ]);
        });
    }
}
