<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\GudepFormation;
use App\Models\GudepOfficer;
use App\Models\DocumentSignatoryProfile;
use App\Models\GudepProgramSheet;

final class GudepProgramSignatories
{
    /**
     * Annual plans belong to a Gudep Putra/Putri rather than a single formation.
     * Include Pembina from every active formation of that gender.
     *
     * @return array<int, array{ref: string, name: string, position: string, scope: string}>
     */
    public static function candidates(
        int $schoolId, int $gudepId, int $yearId, ?int $formationId,
        ?string $date, string $kind, string $letterheadType
    ): array {
        $options = GudepWeeklyPersonnel::candidates($schoolId, $gudepId, $yearId, $formationId, $date);
        if ($kind !== 'annual' || ! in_array($letterheadType, ['male', 'female'], true)) {
            return $options;
        }

        $gender = $letterheadType === 'male' ? 'putra' : 'putri';
        $formations = GudepFormation::query()->where('school_id', $schoolId)
            ->where('scout_group_id', $gudepId)
            ->where('academic_year_id', $yearId)->where('is_active', true)
            ->where(fn ($query) => $query->where('gender', $gender)->orWhereNull('gender'))
            ->pluck('id');
        if ($formations->isEmpty()) {
            return $options;
        }

        $asOf = $date ?: now()->toDateString();
        $officers = GudepOfficer::query()->where('school_id', $schoolId)
            ->where('scout_group_id', $gudepId)
            ->where('scope', 'satuan')
            ->whereIn('gudep_formation_id', $formations)
            ->whereDate('started_at', '<=', $asOf)
            ->where(fn ($query) => $query->whereNull('ended_at')->orWhereDate('ended_at', '>=', $asOf))
            ->with('coach')->get();
        $profiles = DocumentSignatoryProfile::query()->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereIn('user_id', $officers->pluck('coach.user_id')->filter()->unique())
            ->get()->keyBy('user_id');
        $known = array_column($options, null, 'ref');
        foreach ($officers as $officer) {
            $ref = 'officer:'.$officer->id;
            $profile = $officer->coach?->user_id ? $profiles->get($officer->coach->user_id) : null;
            $profileType = strtoupper(trim((string) ($profile?->identifier_type ?? '')));
            $profileNumber = trim((string) ($profile?->identifier_number ?? ''));
            $nta = $profileType === 'NTA' ? $profileNumber : '';
            $nip = $profileType === 'NIP' ? $profileNumber : trim((string) $officer->coach?->nip);
            $known[$ref] = [
                'ref' => $ref,
                'name' => (string) $officer->person_name,
                'position' => (string) $officer->position,
                'scope' => (string) $officer->scope,
                'identity' => $nta !== '' ? 'NTA. '.$nta : ($nip !== '' ? 'NIP. '.$nip : ''),
            ];
        }

        return array_values($known);
    }

    /**
     * @return array<int, array{ref: string, name: string, position: string, scope: string}>
     */
    public static function eligible(array $personnel, string $role, ?string $kind = null): array
    {
        return array_values(array_filter($personnel, static function (array $candidate) use ($role, $kind, $personnel): bool {
            $position = mb_strtolower(trim($candidate['position']));
            $scope = $candidate['scope'];

            return match ($role) {
                'kamabigus' => $scope === 'mabi'
                    && ($position === 'ketua'
                        || str_contains($position, 'kamabigus')
                        || str_contains($position, 'ka mabigus')
                        || str_contains($position, 'ka. mabigus')
                        || str_contains($position, 'ketua mabi')
                        || str_contains($position, 'ketua majelis pembimbing')),
                'pembina' => in_array($scope, ['gudep', 'satuan'], true)
                    && str_contains($position, 'pembina'),
                'pelaksana' => in_array($candidate, GudepWeeklyPersonnel::signers(
                    $personnel, $kind
                ), true),
                default => false,
            };
        }));
    }

    /**
     * @param  array<int, array{ref: string, name: string, position: string, scope: string}>  $personnel
     * @return array<string, mixed>
     */
    public static function snapshot(GudepProgramSheet $sheet, array $personnel): array
    {
        $isWeekly = $sheet->kind === 'weekly';
        $ackRole = $isWeekly ? 'pembina' : 'kamabigus';
        $signRole = $isWeekly ? 'pelaksana' : 'pembina';
        $ack = GudepWeeklyPersonnel::find(self::eligible($personnel, $ackRole), $sheet->acknowledged_by_ref);
        $sign = GudepWeeklyPersonnel::find(self::eligible($personnel, $signRole, $sheet->scoutLevel?->name), $sheet->signed_by_ref);

        return [
            'acknowledger' => $ack,
            'signer' => $sign,
            'acknowledger_role' => $isWeekly ? 'Pembina Gugusdepan' : 'Ketua Majelis Pembimbing Gugusdepan',
            'signer_role' => $isWeekly ? GudepWeeklyPersonnel::signerRole($sheet->scoutLevel?->name) : 'Pembina Gugusdepan',
            'role' => $isWeekly ? GudepWeeklyPersonnel::signerRole($sheet->scoutLevel?->name) : 'Pembina Gugusdepan',
        ];
    }
}
