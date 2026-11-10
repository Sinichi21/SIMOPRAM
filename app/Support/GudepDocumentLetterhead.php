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

    /**
     * Immutable representation of the exact values consumed by the
     * Persuratan letterhead, not a separately invented letterhead format.
     *
     * @return array<string, mixed>
     */
    public static function resolve(School $school, ScoutGroup $gudep, string $type, bool $requireNumber = false): array
    {
        if (! in_array($type, self::TYPES, true)) {
            throw ValidationException::withMessages(['letterheadType' => 'Jenis kop tidak valid.']);
        }

        $profile = ScoutAdministrationProfile::query()
            ->where('school_id', $school->id)->where('type', $type)->first();

        if ($profile !== null && ! $profile->is_active) {
            throw ValidationException::withMessages(['letterheadType' => 'Profil Persuratan yang dipilih belum aktif.']);
        }

        $setting = $school->documentSetting;
        // Persuratan reads Gudep numbers from SchoolDocumentSetting.
        // Keep the same precedence and do not synthesize a new Mabigus subtitle.
        $maleNumber = trim((string) ($setting?->gudep_male_number ?: $gudep->male_number));
        $femaleNumber = trim((string) ($setting?->gudep_female_number ?: $gudep->female_number));
        $required = match ($type) {
            'male' => $maleNumber !== '',
            'female' => $femaleNumber !== '',
            default => $maleNumber !== '' || $femaleNumber !== '',
        };
        if ($requireNumber && ! $required) {
            throw ValidationException::withMessages(['letterheadType' => 'Nomor Gugusdepan pada pengaturan Persuratan belum tersedia.']);
        }

        return [
            'type' => $type,
            'title' => $profile?->letterhead_title,
            'subtitle' => $profile?->letterhead_subtitle,
            'address' => $profile?->letterhead_address,
            'school_name' => $school->name,
            'school_city' => $school->city,
            'school_address' => $school->address,
            'postal_code' => $school->postal_code,
            'gudep_address' => $gudep->secretariat_address,
            'male_number' => $maleNumber,
            'female_number' => $femaleNumber,
        ];
    }
}
