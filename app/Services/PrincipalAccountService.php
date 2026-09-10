<?php

namespace App\Services;

use App\Models\DocumentSignatoryProfile;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PrincipalAccountService
{
    public function create(string $name, string $email): User
    {
        abort_unless(auth()->user()?->can('user_approvals.manage'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');
        $data = Validator::make([
            'principalName' => trim($name),
            'principalEmail' => mb_strtolower(trim($email)),
        ], [
            'principalName' => ['required', 'string', 'max:255'],
            'principalEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
        ])->validate();

        return DB::transaction(function () use ($data, $schoolId): User {
            $user = User::query()->create([
                'name' => $data['principalName'],
                'email' => $data['principalEmail'],
                'password' => Str::random(64),
                'system_role' => 'principal',
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'is_active' => false,
                'activation_pending' => true,
            ]);
            SchoolUserMembership::query()->create([
                'school_id' => $schoolId, 'user_id' => $user->id,
                'is_active' => true, 'joined_at' => now()->toDateString(),
            ]);
            $previousTeamId = getPermissionsTeamId();
            try {
                setPermissionsTeamId($schoolId);
                $user->assignRole('principal');
            } finally {
                setPermissionsTeamId($previousTeamId);
                $user->unsetRelation('roles')->unsetRelation('permissions');
            }
            DocumentSignatoryProfile::query()->create([
                'school_id' => $schoolId, 'user_id' => $user->id,
                'position' => 'Kepala Sekolah', 'is_active' => true,
            ]);

            return $user;
        });
    }
}
