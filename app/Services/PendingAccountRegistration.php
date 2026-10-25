<?php

namespace App\Services;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PendingAccountRegistration
{
    /** @param array<string, mixed> $data */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $school = School::query()->whereKey($data['school_id'])->where('is_active', true)->firstOrFail();
            $role = $data['role'];
            $registrationData = [];
            $name = trim($data['name'] ?? '');

            if ($role === 'student') {
                $identifier = trim($data['student_identifier']);
                $student = Student::query()->where('school_id', $school->id)
                    ->where('status', 'active')->whereNull('user_id')
                    ->where(fn ($query) => $query->where('nis', $identifier)->orWhere('nisn', $identifier))
                    ->lockForUpdate()->first();
                if (! $student) {
                    throw ValidationException::withMessages(['student_identifier' => 'Data siswa tidak tersedia atau sudah terhubung ke akun lain.']);
                }
                $name = $student->name;
                $registrationData = ['student_id' => $student->id, 'student_identifier' => $identifier];
            } elseif ($role === 'coach') {
                $registrationData = ['nta' => trim($data['nta'] ?? ''), 'position' => trim($data['coach_position'])];
            } else {
                $registrationData = ['position' => trim($data['admin_position'])];
            }

            $user = new User;
            $user->forceFill([
                'name' => $name,
                'email' => strtolower(trim($data['email'])),
                'phone' => trim($data['phone'] ?? '') ?: null,
                'password' => Hash::make($data['password']),
                'requested_school_id' => $school->id,
                'requested_role' => $role,
                'registration_data' => json_encode($registrationData, JSON_THROW_ON_ERROR),
                'approval_status' => 'pending',
                'is_active' => false,
                'activation_pending' => false,
            ]);
            $user->save();
            event(new Registered($user));

            return $user;
        });
    }
}
