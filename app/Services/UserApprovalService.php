<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\SchoolUserMembership;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserApprovalService
{
    public function approve(User $user, User $approver, int $schoolId): void
    {
        abort_unless(app(SchoolContext::class)->id() === $schoolId, 409);

        if (
            $user->approval_status !== 'pending'
            || (int) $user->requested_school_id !== $schoolId
        ) {
            throw ValidationException::withMessages([
                'approval' => 'Permintaan persetujuan tidak valid untuk sekolah aktif.',
            ]);
        }

        DB::transaction(function () use ($user, $approver, $schoolId): void {
            $user->update([
                'system_role' => $user->requested_role,
                'approval_status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'is_active' => true,
            ]);

            SchoolUserMembership::query()->updateOrCreate(
                ['school_id' => $schoolId, 'user_id' => $user->id],
                ['is_active' => true, 'joined_at' => now()->toDateString(), 'left_at' => null]
            );

            $previousTeamId = getPermissionsTeamId();

            try {
                setPermissionsTeamId($schoolId);
                $user->unsetRelation('roles')->unsetRelation('permissions');
                $user->syncRoles([$user->requested_role]);
            } finally {
                setPermissionsTeamId($previousTeamId);
                $approver->unsetRelation('roles')->unsetRelation('permissions');
            }

            if ($user->requested_role === 'student' && ! $user->student()->exists()) {
                Student::query()->create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'status' => 'active',
                ]);
            }

            if ($user->requested_role === 'coach' && ! $user->coach()->exists()) {
                Coach::query()->create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'is_active' => true,
                ]);
            }
        });
    }

    public function reject(User $user, User $approver, int $schoolId): void
    {
        abort_unless(app(SchoolContext::class)->id() === $schoolId, 409);

        if (
            $user->approval_status !== 'pending'
            || (int) $user->requested_school_id !== $schoolId
        ) {
            throw ValidationException::withMessages([
                'approval' => 'Permintaan persetujuan tidak valid untuk sekolah aktif.',
            ]);
        }

        $user->update([
            'approval_status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'is_active' => false,
        ]);
    }
}
