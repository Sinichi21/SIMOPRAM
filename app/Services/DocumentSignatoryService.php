<?php

namespace App\Services;

use App\Models\DocumentSignatoryProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DocumentSignatoryService
{
    /** @return Collection<int, User> */
    public function usersForSchool(int $schoolId): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($schoolId): void {
                $query->whereHas('schoolMemberships', function ($membership) use ($schoolId): void {
                    $membership->where('school_id', $schoolId)->where('is_active', true)->whereNull('left_at');
                })->orWhere(function ($legacy) use ($schoolId): void {
                    $legacy->whereDoesntHave('schoolMemberships', fn ($membership) => $membership->where('school_id', $schoolId))
                        ->whereHas('coach', fn ($coach) => $coach->where('school_id', $schoolId)->where('is_active', true));
                });
            })
            ->with(['coach' => fn ($query) => $query->withTrashed()])
            ->orderBy('name')
            ->get();
    }

    /** @return array{user_id:int|null,name:string,position:string,identifier_type:string,identifier_number:string,identity:string} */
    public function resolve(?int $userId, int $schoolId): array
    {
        if (! $userId) {
            return $this->empty();
        }

        $user = $this->usersForSchool($schoolId)->firstWhere('id', $userId);

        if (! $user) {
            throw ValidationException::withMessages([
                'signatory' => 'User penandatangan tidak aktif atau bukan anggota sekolah aktif.',
            ]);
        }

        $profile = DocumentSignatoryProfile::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $position = trim((string) ($profile?->position ?: $user->coach?->position));
        $identifierType = strtoupper(trim((string) ($profile?->identifier_type ?? '')));
        $identifierNumber = trim((string) ($profile?->identifier_number ?? ''));

        // Compatibility: pada data lama Coach.nip dipakai sebagai NTA.
        if ($identifierNumber === '' && filled($user->coach?->nip)) {
            $identifierType = 'NTA';
            $identifierNumber = trim((string) $user->coach->nip);
        }

        $identity = $identifierNumber === ''
            ? ''
            : (($identifierType !== '' ? $identifierType.'. ' : '').$identifierNumber);

        return [
            'user_id' => (int) $user->id,
            'name' => trim((string) $user->name),
            'position' => $position,
            'identifier_type' => $identifierType,
            'identifier_number' => $identifierNumber,
            'identity' => $identity,
        ];
    }

    public function saveProfile(
        int $schoolId,
        int $userId,
        ?string $position,
        ?string $identifierType,
        ?string $identifierNumber
    ): DocumentSignatoryProfile {
        $this->resolve($userId, $schoolId);

        $identifierType = strtoupper(trim((string) $identifierType));
        $identifierNumber = trim((string) $identifierNumber);

        if ($identifierNumber !== '' && ! in_array($identifierType, ['NIP', 'NTA'], true)) {
            throw ValidationException::withMessages([
                'profileIdentifierType' => 'Jenis identitas harus NIP atau NTA.',
            ]);
        }

        return DocumentSignatoryProfile::query()->updateOrCreate(
            ['school_id' => $schoolId, 'user_id' => $userId],
            [
                'position' => $this->nullable($position),
                'identifier_type' => $identifierNumber !== '' ? $identifierType : null,
                'identifier_number' => $identifierNumber !== '' ? $identifierNumber : null,
                'is_active' => true,
            ]
        );
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @return array{user_id:null,name:string,position:string,identifier_type:string,identifier_number:string,identity:string} */
    private function empty(): array
    {
        return [
            'user_id' => null,
            'name' => '',
            'position' => '',
            'identifier_type' => '',
            'identifier_number' => '',
            'identity' => '',
        ];
    }
}
