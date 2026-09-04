<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CoachAccountService
{
    public function createAccount(Coach $coach, string $email, string $password): User
    {
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        if ($coach->user_id) {
            throw ValidationException::withMessages(['email' => 'Pembina ini sudah memiliki akun.']);
        }

        return DB::transaction(function () use ($coach, $email, $password, $schoolId): User {
            $user = User::query()->create([
                'name' => $coach->name,
                'email' => mb_strtolower(trim($email)),
                'password' => $password,
                'system_role' => 'coach',
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'is_active' => true,
            ]);

            SchoolUserMembership::query()->create([
                'school_id' => $schoolId,
                'user_id' => $user->id,
                'is_active' => true,
                'joined_at' => now()->toDateString(),
            ]);

            $previousTeamId = getPermissionsTeamId();
            try {
                setPermissionsTeamId($schoolId);
                $user->assignRole('coach');
            } finally {
                setPermissionsTeamId($previousTeamId);
                auth()->user()?->unsetRelation('roles')?->unsetRelation('permissions');
            }

            $coach->update(['user_id' => $user->id]);

            return $user;
        });
    }

    public function resetPassword(Coach $coach, string $password): void
    {
        if (! $coach->user_id) {
            throw ValidationException::withMessages(['password' => 'Pembina belum memiliki akun.']);
        }

        $coach->user()->firstOrFail()->update(['password' => $password]);
    }
}
