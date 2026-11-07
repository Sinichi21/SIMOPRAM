<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\GudepProgramSheet;

final class GudepProgramSignatories
{
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
                    && (str_contains($position, 'kamabigus')
                        || str_contains($position, 'ketua mabigus')
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
