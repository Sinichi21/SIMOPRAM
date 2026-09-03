<?php

namespace App\Services;

use App\Models\AttendanceScoreSetting;

class AttendanceWeightService
{
    public function setting(): ?AttendanceScoreSetting
    {
        return AttendanceScoreSetting::query()
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Versi konfigurasi
    |--------------------------------------------------------------------------
    |
    | Jika sekolah belum pernah membuat konfigurasi,
    | default SIMOPRAM dianggap sebagai version 1.
    |
    */

    public function version(): int
    {
        return (int) (
            $this->setting()?->version
            ?? 1
        );
    }

    public function percentages(): array
    {
        $setting =
            $this->setting();

        if (! $setting) {
            return AttendanceScoreSetting::defaultWeights();
        }

        return $setting->percentages();
    }

    public function factors(): array
    {
        return collect(
            $this->percentages()
        )
            ->map(
                fn (
                    float $percentage
                ): float => $percentage / 100
            )
            ->all();
    }

    public function factorForStatus(
        string $status
    ): float {
        return (float) (
            $this->factors()[
                $status
            ] ?? 0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Simpan konfigurasi
    |--------------------------------------------------------------------------
    |
    | Version hanya naik jika nilai bobot benar-benar berubah.
    |
    */

    public function savePercentages(
        array $percentages,
        ?int $updatedBy = null
    ): AttendanceScoreSetting {
        $percentages =
            $this->normalize(
                $percentages
            );

        $oldPercentages =
            $this->percentages();

        $oldVersion =
            $this->version();

        $setting =
            AttendanceScoreSetting::query()
                ->first();

        /*
        |--------------------------------------------------------------------------
        | Belum pernah ada setting
        |--------------------------------------------------------------------------
        */

        if (! $setting) {
            $defaults =
                AttendanceScoreSetting::defaultWeights();

            $changedFromDefault =
                $this->weightsAreDifferent(
                    $defaults,
                    $percentages
                );

            $setting =
                new AttendanceScoreSetting;

            /*
            |--------------------------------------------------------------------------
            | Default implisit adalah version 1.
            |
            | Jika setting pertama sama dengan default:
            | version tetap 1.
            |
            | Jika langsung diubah dari default:
            | version menjadi 2.
            |--------------------------------------------------------------------------
            */

            $setting->version =
                $changedFromDefault
                    ? 2
                    : 1;
        } else {

            $changed =
                $this->weightsAreDifferent(
                    $setting->percentages(),
                    $percentages
                );

            if ($changed) {
                $setting->version =
                    max(
                        1,
                        (int) $setting->version
                    ) + 1;
            }
        }

        $setting->fill([
            'present_weight' => $percentages['present'],

            'late_weight' => $percentages['late'],

            'sick_weight' => $percentages['sick'],

            'excused_weight' => $percentages['excused'],

            'absent_weight' => $percentages['absent'],

            'updated_by' => $updatedBy,
        ]);

        $setting->save();

        $newPercentages =
            $setting->percentages();

        $newVersion =
            (int) $setting->version;

        if (
            $oldPercentages
            !== $newPercentages
            ||
            $oldVersion !== $newVersion
        ) {
            app(
                AssessmentAuditService::class
            )
                ->record(
                    action: 'attendance_weight.updated',

                    subject: $setting,

                    description: 'Bobot kehadiran diperbarui.',

                    oldValues: [
                        'weights' => $oldPercentages,

                        'version' => $oldVersion,
                    ],

                    newValues: [
                        'weights' => $newPercentages,

                        'version' => $newVersion,
                    ],

                    module: 'attendance'
                );
        }

        return $setting;
    }

    protected function normalize(
        array $weights
    ): array {
        $defaults =
            AttendanceScoreSetting::defaultWeights();

        return collect(
            array_keys($defaults)
        )
            ->mapWithKeys(
                function (
                    string $status
                ) use (
                    $weights,
                    $defaults
                ): array {
                    $value =
                        (float) (
                            $weights[$status]
                            ?? $defaults[$status]
                        );

                    $value =
                        min(
                            100,
                            max(
                                0,
                                $value
                            )
                        );

                    return [
                        $status => round(
                            $value,
                            2
                        ),
                    ];
                }
            )
            ->all();
    }

    protected function weightsAreDifferent(
        array $oldWeights,
        array $newWeights
    ): bool {
        foreach (
            AttendanceScoreSetting::defaultWeights() as $status => $default
        ) {
            $old =
                (float) (
                    $oldWeights[$status]
                    ?? $default
                );

            $new =
                (float) (
                    $newWeights[$status]
                    ?? $default
                );

            if (
                abs(
                    $old - $new
                ) > 0.0001
            ) {
                return true;
            }
        }

        return false;
    }
}
