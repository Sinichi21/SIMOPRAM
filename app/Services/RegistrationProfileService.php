<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RegistrationProfileService
{
    public function attach(User $user, int $schoolId): void
    {
        if ((int) $user->requested_school_id !== $schoolId) {
            throw ValidationException::withMessages([
                'approval' => 'Sekolah pengajuan tidak sesuai dengan sekolah aktif.',
            ]);
        }

        $data = $this->registrationData($user);

        match ($user->requested_role) {
            'student' => $this->attachStudent($user, $schoolId, $data),
            'coach' => $this->attachCoach($user, $schoolId, $data),
            'school_admin' => null,
            default => throw ValidationException::withMessages([
                'approval' => 'Role pengajuan tidak dikenali.',
            ]),
        };
    }

    private function registrationData(User $user): array
    {
        $value = $user->getRawOriginal('registration_data');

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function attachStudent(User $user, int $schoolId, array $data): void
    {
        $student = Student::query()
            ->whereKey((int) ($data['student_id'] ?? 0))
            ->where('school_id', $schoolId)
            ->first();

        if (! $student) {
            throw ValidationException::withMessages(['approval' => 'Data siswa pada pendaftaran tidak ditemukan.']);
        }

        if ($student->user_id && (int) $student->user_id !== (int) $user->id) {
            throw ValidationException::withMessages(['approval' => 'Data siswa sudah terhubung ke akun lain.']);
        }

        $student->forceFill([
            'user_id' => $user->id,
            'phone' => $student->phone ?: $user->phone,
        ])->save();
    }

    private function attachCoach(User $user, int $schoolId, array $data): void
    {
        $nta = trim((string) ($data['nta'] ?? ''));

        $coach = Coach::query()
            ->where('school_id', $schoolId)
            ->where(function ($query) use ($nta, $user): void {
                if ($nta !== '') {
                    $query->where('nip', $nta);
                } else {
                    $query->where('name', $user->name);
                }
            })
            ->first();

        if ($coach && $coach->user_id && (int) $coach->user_id !== (int) $user->id) {
            throw ValidationException::withMessages(['approval' => 'Data pembina sudah terhubung ke akun lain.']);
        }

        $coach ??= new Coach;
        $coach->forceFill([
            'school_id' => $schoolId,
            'user_id' => $user->id,
            'name' => $user->name,
            'nip' => $nta !== '' ? $nta : $coach->nip,
            'phone' => $user->phone ?: $coach->phone,
            'is_active' => true,
        ])->save();
    }
}
