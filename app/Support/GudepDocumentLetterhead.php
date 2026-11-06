<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\School;
use App\Models\ScoutAdministrationProfile;
use App\Models\ScoutGroup;
use Illuminate\Validation\ValidationException;

final class GudepDocumentLetterhead
{
    public const TYPES = ['mabigus', 'male', 'female'];

    /** @return array<string, mixed> */
    public static function resolve(School $school, ScoutGroup $gudep, string $type, bool $requireNumber = false): array
    {
        if (! in_array($type, self::TYPES, true)) {
            throw ValidationException::withMessages(['letterheadType' => 'Cakupan kop belum ditentukan.']);
        }

        $profile = ScoutAdministrationProfile::query()
            ->where('school_id', $school->id)->where('type', $type)->first();

        if ($profile !== null && ! $profile->is_active) {
            throw ValidationException::withMessages(['letterheadType' => 'Profil administrasi untuk kop ini belum aktif.']);
        }

        $setting = $school->documentSetting;
        $maleNumber = trim((string) ($gudep->male_number ?: $setting?->gudep_male_number));
        $femaleNumber = trim((string) ($gudep->female_number ?: $setting?->gudep_female_number));
        $number = match ($type) {
            'male' => $maleNumber,
            'female' => $femaleNumber,
            default => implode(' - ', array_filter([$maleNumber, $femaleNumber],
                fn (string $value): bool => $value !== '')),
        };

        if ($requireNumber && $number === '') {
            throw ValidationException::withMessages(['letterheadType' => 'Nomor Gudep untuk kop yang dipilih belum tersedia.']);
        }

        $number = $number ?: '–';

        $city = mb_strtoupper(trim((string) ($school->city ?: $gudep->kwarcab)));
        $address = trim((string) ($profile?->letterhead_address ?: $gudep->secretariat_address ?: $school->address));
        $postal = trim((string) $school->postal_code);
        if ($postal !== '' && ! str_contains($address, $postal)) {
            $address = trim($address.' '.$postal);
        }

        $title = trim((string) ($profile?->letterhead_title ?: 'GERAKAN PRAMUKA'));
        $category = match ($type) {
            'male' => 'GUGUSDEPAN PUTRA',
            'female' => 'GUGUSDEPAN PUTRI',
            default => 'MAJELIS PEMBIMBING GUGUSDEPAN',
        };

        $subtitle = trim((string) ($profile?->letterhead_subtitle ?: $category.' '.$city.' '.$number));

        return [
            'type' => $type,
            'title' => $title,
            'subtitle' => $subtitle,
            'category' => $category,
            'number' => $number,
            'school' => $school->name,
            'address' => $address,
            'city' => $city,
        ];
    }
}
