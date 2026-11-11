<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\GudepProgramSheet;
use App\Models\GudepRkagDecision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GudepRkagApproval
{
    public static function snapshotHash(GudepProgramSheet $sheet): string
    {
        $entries = array_values(array_filter(
            $sheet->entries ?? [],
            static fn (array $entry): bool => ($entry['section'] ?? '') === 'keuangan'
        ));
        $financial = array_map(static fn (array $entry): array => [
            'key' => $entry['key'] ?? null,
            'parent_key' => $entry['parent_key'] ?? null,
            'description' => $entry['description'] ?? null,
            'finance_type' => $entry['finance_type'] ?? null,
            'amount' => $entry['amount'] ?? null,
            'currency' => $entry['currency'] ?? null,
            'detail' => $entry['detail'] ?? null,
            'rkag_lines' => $entry['rkag_lines'] ?? [],
        ], $entries);

        return hash('sha256', json_encode([
            'school_id' => $sheet->school_id,
            'scout_group_id' => $sheet->scout_group_id,
            'academic_year_id' => $sheet->academic_year_id,
            'sheet_id' => $sheet->id,
            'financial' => $financial,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array{status: string, stale: bool, last: ?GudepRkagDecision}
     */
    public static function state(GudepProgramSheet $sheet): array
    {
        $last = GudepRkagDecision::query()
            ->where('school_id', $sheet->school_id)
            ->where('gudep_program_sheet_id', $sheet->id)
            ->orderByDesc('id')->first();

        $stale = $last !== null && $last->snapshot_hash !== self::snapshotHash($sheet);
        $status = match (true) {
            $last === null => 'draft',
            $stale => 'stale',
            $last->action === 'submit' => 'submitted',
            $last->action === 'approve' => 'approved',
            $last->action === 'reject' => 'rejected',
            default => 'draft',
        };

        return compact('status', 'stale', 'last');
    }

    /**
     * Creates an audit event without modifying an existing decision, cash
     * transaction, published document, or program sheet.
     */
    public static function transition(GudepProgramSheet $sheet, User $actor, string $action, string $notes = ''): GudepRkagDecision
    {
        abort_unless((int) app(SchoolContext::class)->id() === (int) $sheet->school_id, 403);

        if (! in_array($action, ['submit', 'approve', 'reject'], true)) {
            throw ValidationException::withMessages(['rkagApproval' => 'Aksi RKAG tidak valid.']);
        }

        if ($action === 'submit') {
            abort_unless($actor->can('gudep.manage') || $actor->can('gudep.finance.manage'), 403);
        } else {
            abort_unless($actor->can('gudep.finance.manage'), 403);
        }

        $notes = trim($notes);
        if (mb_strlen($notes) > 1000 || ($action === 'reject' && $notes === '')) {
            throw ValidationException::withMessages(['rkagApprovalNotes' => 'Alasan penolakan wajib diisi, maksimal 1000 karakter.']);
        }

        return DB::transaction(static function () use ($sheet, $actor, $action, $notes): GudepRkagDecision {
            $locked = GudepProgramSheet::query()
                ->where('school_id', $sheet->school_id)
                ->where('scout_group_id', $sheet->scout_group_id)
                ->where('academic_year_id', $sheet->academic_year_id)
                ->where('kind', 'annual')
                ->lockForUpdate()->findOrFail($sheet->id);

            $financial = array_filter(
                $locked->entries ?? [],
                static fn (array $row): bool => ($row['section'] ?? '') === 'keuangan'
                    && in_array($row['finance_type'] ?? '', ['income', 'expense'], true)
            );
            if ($financial === []) {
                throw ValidationException::withMessages(['rkagApproval' => 'Program Tahunan belum memiliki rencana pemasukan atau pengeluaran.']);
            }

            $state = self::state($locked);
            if ($action === 'submit' && ! in_array($state['status'], ['draft', 'rejected', 'stale'], true)) {
                throw ValidationException::withMessages(['rkagApproval' => 'RKAG ini sudah diajukan atau disetujui.']);
            }

            if ($action !== 'submit' && $state['status'] !== 'submitted') {
                throw ValidationException::withMessages(['rkagApproval' => 'Keputusan hanya dapat diberikan pada pengajuan RKAG yang masih berlaku.']);
            }

            if ($action !== 'submit' && (int) $state['last']?->actor_id === (int) $actor->id) {
                throw ValidationException::withMessages(['rkagApproval' => 'Pengaju tidak dapat menyetujui atau menolak RKAG miliknya sendiri.']);
            }

            return GudepRkagDecision::query()->create([
                'school_id' => $locked->school_id,
                'scout_group_id' => $locked->scout_group_id,
                'academic_year_id' => $locked->academic_year_id,
                'gudep_program_sheet_id' => $locked->id,
                'actor_id' => $actor->id,
                'action' => $action,
                'snapshot_hash' => self::snapshotHash($locked),
                'notes' => $notes ?: null,
            ]);
        });
    }
}
