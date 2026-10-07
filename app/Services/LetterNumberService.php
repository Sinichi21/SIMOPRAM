<?php

namespace App\Services;

use App\Enums\ScoutAdministrationType;
use App\Models\LetterField;
use App\Models\LetterNumberSequence;
use App\Models\LetterType;
use App\Models\SchoolDocumentSetting;
use App\Models\SchoolLetterSetting;
use App\Models\ScoutAdministrationProfile;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class LetterNumberService
{
    public function nextOutgoing(
        int $schoolId,
        CarbonInterface $date,
        LetterType $type,
        LetterField $field,
        string|ScoutAdministrationType $administrationType = ScoutAdministrationType::Mabigus
    ): string {
        $administrationType = $this->normalizeType($administrationType);
        $legacy = SchoolLetterSetting::withoutGlobalScope('school')->where('school_id', $schoolId)->firstOrFail();
        $profile = $this->profile($schoolId, $administrationType);
        $number = $this->nextSequence('outgoing', $schoolId, $administrationType->value, (int) $date->year);

        $gudep = $this->gudepTokens($schoolId, (string) $legacy->gudep_code, $administrationType);

        return $this->render($profile?->number_format ?: $legacy->number_format, [
            'sequence' => (string) $number,
            'sequence_padded' => str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'type' => $type->code,
            'month' => str_pad((string) $date->month, 2, '0', STR_PAD_LEFT),
            'month_roman' => $this->romanMonth((int) $date->month),
            'year' => (string) $date->year,
            'year_short' => substr((string) $date->year, -2),
            'gudep' => $gudep['gudep'],
            'gudep_male' => $gudep['gudep_male'],
            'gudep_female' => $gudep['gudep_female'],
            'gudep_pair' => $gudep['gudep_pair'],
            'administration' => $administrationType->value,
            'administration_label' => $administrationType->shortLabel(),
            'field' => $field->code,
        ]);
    }

    public function nextAgenda(
        int $schoolId,
        CarbonInterface $date,
        string|ScoutAdministrationType $administrationType = ScoutAdministrationType::Mabigus
    ): string {
        $administrationType = $this->normalizeType($administrationType);
        $legacy = SchoolLetterSetting::withoutGlobalScope('school')->where('school_id', $schoolId)->firstOrFail();
        $profile = $this->profile($schoolId, $administrationType);
        $number = $this->nextSequence('incoming', $schoolId, $administrationType->value, (int) $date->year);
        $gudep = $this->gudepTokens($schoolId, (string) $legacy->gudep_code, $administrationType);

        return $this->render($profile?->agenda_format ?: $legacy->agenda_format, [
            'sequence' => (string) $number,
            'sequence_padded' => str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'year' => (string) $date->year,
            'year_short' => substr((string) $date->year, -2),
            'month' => str_pad((string) $date->month, 2, '0', STR_PAD_LEFT),
            'month_roman' => $this->romanMonth((int) $date->month),
            'gudep' => $gudep['gudep'],
            'gudep_male' => $gudep['gudep_male'],
            'gudep_female' => $gudep['gudep_female'],
            'gudep_pair' => $gudep['gudep_pair'],
            'administration' => $administrationType->value,
            'administration_label' => $administrationType->shortLabel(),
        ]);
    }

    /** Backward compatibility with Persuratan MVP. */
    public function next(string $direction, int $schoolId, int $year, int $month): string
    {
        $date = now()->setDate($year, $month, 1);
        if ($direction === 'incoming') {
            return $this->nextAgenda($schoolId, $date, ScoutAdministrationType::Mabigus);
        }
        throw new \InvalidArgumentException('Surat keluar v2 memerlukan LetterType dan LetterField. Gunakan nextOutgoing().');
    }

    public function setLastNumber(
        string $direction,
        int $schoolId,
        int $year,
        int $lastNumber,
        string|ScoutAdministrationType $administrationType = ScoutAdministrationType::Mabigus
    ): void {
        $administrationType = $this->normalizeType($administrationType);

        LetterNumberSequence::withoutGlobalScope('school')->updateOrCreate(
            [
                'school_id' => $schoolId,
                'direction' => $direction,
                'administration_type' => $administrationType->value,
                'year' => $year,
            ],
            ['last_number' => max(0, $lastNumber)]
        );
    }

    /** @return array{gudep:string,gudep_male:string,gudep_female:string,gudep_pair:string} */
    public function gudepTokens(
        int $schoolId,
        string $fallback = '',
        string|ScoutAdministrationType $administrationType = ScoutAdministrationType::Mabigus
    ): array {
        $administrationType = $this->normalizeType($administrationType);
        $document = SchoolDocumentSetting::withoutGlobalScope('school')->where('school_id', $schoolId)->first();

        $male = trim((string) $document?->gudep_male_number);
        $female = trim((string) $document?->gudep_female_number);
        $pair = $this->combinedGudep($male, $female, trim($fallback));

        $current = match ($administrationType) {
            ScoutAdministrationType::Male => $male !== '' ? $male : $pair,
            ScoutAdministrationType::Female => $female !== '' ? $female : $pair,
            ScoutAdministrationType::Mabigus => $pair,
        };

        return [
            'gudep' => $current,
            'gudep_male' => $male,
            'gudep_female' => $female,
            'gudep_pair' => $pair,
        ];
    }

    private function nextSequence(string $direction, int $schoolId, string $administrationType, int $year): int
    {
        return DB::transaction(function () use ($direction, $schoolId, $administrationType, $year): int {
            $sequence = LetterNumberSequence::withoutGlobalScope('school')
                ->where('school_id', $schoolId)
                ->where('direction', $direction)
                ->where('administration_type', $administrationType)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = LetterNumberSequence::withoutGlobalScope('school')->create([
                    'school_id' => $schoolId,
                    'direction' => $direction,
                    'administration_type' => $administrationType,
                    'year' => $year,
                    'last_number' => 0,
                ]);
            }

            $sequence->increment('last_number');

            return (int) $sequence->fresh()->last_number;
        }, 3);
    }

    private function profile(int $schoolId, ScoutAdministrationType $type): ?ScoutAdministrationProfile
    {
        return ScoutAdministrationProfile::withoutGlobalScope('school')
            ->where('school_id', $schoolId)
            ->where('type', $type->value)
            ->where('is_active', true)
            ->first();
    }

    private function normalizeType(string|ScoutAdministrationType $type): ScoutAdministrationType
    {
        return $type instanceof ScoutAdministrationType
            ? $type
            : ScoutAdministrationType::tryFrom($type) ?? ScoutAdministrationType::Mabigus;
    }

    private function combinedGudep(string $male, string $female, string $fallback): string
    {
        if ($male === '' && $female === '') {
            return $fallback;
        }
        if ($male === '') {
            return $female;
        }
        if ($female === '') {
            return $male;
        }

        if (str_contains($male, '.') && str_contains($female, '.')) {
            [$prefixMale, $suffixMale] = explode('.', $male, 2);
            [$prefixFemale, $suffixFemale] = explode('.', $female, 2);
            if ($prefixMale === $prefixFemale) {
                return $prefixMale.'.'.$suffixMale.'-'.$suffixFemale;
            }
        }

        return $male.'-'.$female;
    }

    private function render(string $format, array $tokens): string
    {
        foreach ($tokens as $key => $value) {
            $format = str_replace('{'.$key.'}', $value, $format);
        }

        return trim($format);
    }

    private function romanMonth(int $month): string
    {
        return [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'][$month] ?? (string) $month;
    }
}
