<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\SchoolUserMembership;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountActivationService
{
    public function linkAccount(Coach|Student $profile, string $email): User
    {
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId && (int) $profile->school_id === $schoolId, 404);
        $role = $profile instanceof Coach ? 'coach' : 'student';
        abort_unless(auth()->user()?->can($role === 'coach' ? 'coach_accounts.manage' : 'student_accounts.manage'), 403);

        return DB::transaction(function () use ($profile, $email, $schoolId, $role): User {
            $profile = $profile->newQuery()->lockForUpdate()->findOrFail($profile->id);
            if ($profile->user_id) {
                throw ValidationException::withMessages(['email' => 'Data ini sudah memiliki akun.']);
            }

            $user = User::query()->where('email', mb_strtolower(trim($email)))->lockForUpdate()->first();
            if ($user && ($user->system_role !== $role || $user->approval_status !== 'approved'
                || (! $user->is_active && ! $user->activation_pending))) {
                throw ValidationException::withMessages(['email' => 'Akun ini tidak dapat dihubungkan. Hubungi pengelola akun.']);
            }

            if ($user && $profile->newQuery()->withTrashed()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['email' => 'Akun sudah terhubung dengan data lain di sekolah ini.']);
            }

            $user ??= User::query()->create([
                'name' => $profile->name,
                'email' => mb_strtolower(trim($email)),
                'phone' => $profile->phone,
                'password' => Str::random(64),
                'system_role' => $role,
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'is_active' => false,
                'activation_pending' => true,
            ]);

            SchoolUserMembership::query()->firstOrCreate(
                ['school_id' => $schoolId, 'user_id' => $user->id],
                ['is_active' => true, 'joined_at' => now()->toDateString()]
            );

            $previousTeamId = getPermissionsTeamId();
            try {
                setPermissionsTeamId($schoolId);
                $user->unsetRelation('roles')->unsetRelation('permissions')->assignRole($role);
            } finally {
                setPermissionsTeamId($previousTeamId);
                $user->unsetRelation('roles')->unsetRelation('permissions');
                auth()->user()?->unsetRelation('roles')->unsetRelation('permissions');
            }

            $profile->update(['user_id' => $user->id]);

            return $user;
        });
    }

    public function sendLink(User $user, string $channel = 'email'): ?string
    {
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId && $user->schoolMemberships()->where('school_id', $schoolId)
            ->where('is_active', true)->whereNull('left_at')->exists(), 404);
        $permission = match ($user->system_role) {
            'coach' => 'coach_accounts.manage',
            'student' => 'student_accounts.manage',
            default => 'user_approvals.manage',
        };
        abort_unless(auth()->user()?->can($permission), 403);
        abort_if($user->isSystemAdmin() || ($user->system_role === 'school_admin' && ! auth()->user()->isSuperAdmin()), 403);
        if ($user->approval_status !== 'approved' || (! $user->is_active && ! $user->activation_pending)) {
            throw ValidationException::withMessages(['activation' => 'Akun belum disetujui atau telah dinonaktifkan.']);
        }
        if (! in_array($channel, ['email', 'share'], true)) {
            throw ValidationException::withMessages(['activation' => 'Saluran pengiriman tidak valid.']);
        }

        $url = null;
        try {
            $status = Password::broker()->sendResetLink(['email' => $user->email], function (User $recipient, string $token) use ($channel, &$url): void {
                if ($channel === 'email') {
                    $recipient->sendPasswordResetNotification($token);
                } else {
                    $url = route('password.reset', ['token' => $token, 'email' => $recipient->email]);
                }
            });
        } catch (Throwable) {
            Password::broker()->deleteToken($user);
            throw ValidationException::withMessages(['activation' => 'Tautan belum terkirim. Periksa konfigurasi email atau gunakan tombol berbagi tautan.']);
        }
        if ($status !== Password::ResetLinkSent) {
            throw ValidationException::withMessages(['activation' => __($status)]);
        }

        return $url;
    }
}
