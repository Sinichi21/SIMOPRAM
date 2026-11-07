<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\GudepOfficer;
use App\Models\GudepProgramSheet;
use App\Models\ScoutUnitMember;

final class GudepWeeklyPersonnel
{
    public static function signerRole(?string $level): string
    {
        return match (mb_strtoupper(trim((string) $level))) {
            'SIAGA' => 'Pembina',
            'PENGGALANG' => 'Pratama',
            'PENEGAK' => 'Pradana',
            'PANDEGA' => 'Ketua Racana',
            default => 'Penanggung Jawab Satuan',
        };
    }

    /**
     * Personnel options are references to existing Gudep organization data.
     * Unassigned/expired officers and students from another formation are excluded.
     *
     * @return array<int, array{ref: string, name: string, position: string, scope: string}>
     */
    public static function candidates(int $schoolId, int $gudepId, int $yearId, ?int $formationId, ?string $date): array
    {
        $asOf = $date ?: now()->toDateString();
        $officers = GudepOfficer::query()->where('school_id', $schoolId)
            ->where('scout_group_id', $gudepId)
            ->whereDate('started_at', '<=', $asOf)
            ->where(fn ($query) => $query->whereNull('ended_at')->orWhereDate('ended_at', '>=', $asOf))
            ->orderBy('scope')->orderBy('position')->get();

        $options = [];
        foreach ($officers as $officer) {
            if ($officer->scope === 'satuan' && $officer->gudep_formation_id !== null
                && (int) $officer->gudep_formation_id !== (int) $formationId) {
                continue;
            }
            $options[] = [
                'ref' => 'officer:'.$officer->id,
                'name' => (string) $officer->person_name,
                'position' => (string) $officer->position,
                'scope' => (string) $officer->scope,
            ];
        }

        if ($formationId === null) {
            return $options;
        }

        $memberships = ScoutUnitMember::query()
            ->where('school_id', $schoolId)
            ->whereHas('scoutUnit', fn ($query) => $query
                ->where('school_id', $schoolId)
                ->where('scout_group_id', $gudepId)
                ->where('academic_year_id', $yearId)
                ->where('gudep_formation_id', $formationId)
                ->where('is_active', true))
            ->where(fn ($query) => $query->whereNull('joined_at')->orWhereDate('joined_at', '<=', $asOf))
            ->where(fn ($query) => $query->whereNull('left_at')->orWhereDate('left_at', '>=', $asOf))
            ->with('student')->get();

        foreach ($memberships as $membership) {
            if ($membership->student === null || ($membership->student->status !== 'active' && $membership->student->status !== 'aktif')) {
                continue;
            }
            $options[] = [
                'ref' => 'member:'.$membership->id,
                'name' => (string) $membership->student->name,
                'position' => (string) ($membership->position ?: 'Anggota Muda'),
                'scope' => 'muda',
            ];
        }

        return $options;
    }

    /**
     * @param  array<int, array{ref: string, name: string, position: string, scope: string}>  $options
     * @return array{ref: string, name: string, position: string, scope: string}|null
     */
    public static function find(array $options, ?string $ref): ?array
    {
        if ($ref === null || $ref === '') {
            return null;
        }

        foreach ($options as $option) {
            if ($option['ref'] === $ref) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{ref: string, name: string, position: string, scope: string}>  $options
     * @return array<int, array{ref: string, name: string, position: string, scope: string}>
     */
    public static function signers(array $options, ?string $levelName): array
    {
        $role = mb_strtolower(self::signerRole($levelName));

        return array_values(array_filter($options, static function (array $option) use ($role): bool {
            $position = mb_strtolower(trim($option['position']));

            if ($role === 'pembina') {
                return $option['scope'] !== 'muda' && str_starts_with($position, 'pembina');
            }

            // Youth leadership roles must come from the selected unit's
            // membership, not an unrelated adult position or deputy.
            return $option['scope'] === 'muda'
                && ($position === $role || str_starts_with($position, $role.' '));
        }));
    }

    /**
     * @param  array<int, array{ref: string, name: string, position: string, scope: string}>  $options
     * @return array<int, array{ref: string, name: string, position: string, scope: string}>
     */
    public static function acknowledgers(array $options): array
    {
        return array_values(array_filter($options, static fn (array $option): bool => $option['scope'] !== 'muda'
            && in_array($option['scope'], ['gudep', 'mabi', 'satuan'], true)
            && str_contains(mb_strtolower($option['position']), 'pembina')));
    }

    /**
     * @param  array<int, array{ref: string, name: string, position: string, scope: string}>  $options
     * @return array<string, mixed>
     */
    public static function snapshot(GudepProgramSheet $sheet, array $options): array
    {
        $signer = self::find($options, $sheet->signed_by_ref);
        $acknowledger = self::find($options, $sheet->acknowledged_by_ref);

        return [
            'role' => self::signerRole($sheet->scoutLevel?->name),
            'signer' => $signer,
            'acknowledger' => $acknowledger,
            'date' => $sheet->scheduled_for?->toDateString(),
        ];
    }
}
