<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityDelegate;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GlobalActivityAccess
{
    /** @return Collection<int, int> */
    public function schoolAdminIds(User $user): Collection
    {
        if (! $user->is_active) {
            return collect();
        }

        return DB::table('school_user_memberships as memberships')
            ->join('schools', 'schools.id', '=', 'memberships.school_id')
            ->join('model_has_roles as assigned', function ($join) use ($user): void {
                $join->on('assigned.school_id', '=', 'memberships.school_id')->where('assigned.model_id', $user->id)->where('assigned.model_type', $user->getMorphClass());
            })->join('roles', 'roles.id', '=', 'assigned.role_id')
            ->where('memberships.user_id', $user->id)->where('memberships.is_active', true)->whereNull('memberships.left_at')
            ->where('schools.is_active', true)->whereNull('schools.deleted_at')->where('roles.name', 'school_admin')
            ->distinct()->pluck('memberships.school_id')->map(fn ($id): int => (int) $id);
    }

    public function available(User $user, bool $includeRequests = false): Builder
    {
        $query = Activity::withoutGlobalScope('school')->whereNull('school_id');
        if (! $user->is_active) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isSuperAdmin()) {
            return $query;
        }
        $schoolIds = $this->schoolAdminIds($user);

        return $query->where(function (Builder $query) use ($user, $schoolIds, $includeRequests): void {
            $query->where(function (Builder $query) use ($schoolIds, $includeRequests): void {
                $query->whereIn('organizer_school_id', $schoolIds);
                if (! $includeRequests) {
                    $query->where('approval_status', 'approved');
                }
            })->orWhere(function (Builder $query) use ($user): void {
                $query->where('approval_status', 'approved')->whereHas('delegates', fn (Builder $query) => $query->where('user_id', $user->id)->where('status', 'approved'));
            });
        });
    }

    public function canManage(?User $user, Activity $activity): bool
    {
        return $user && $activity->school_id === null && $this->available($user)->whereKey($activity->id)->exists();
    }

    public function authorize(Activity $activity): void
    {
        abort_unless($this->canManage(auth()->user(), $activity), 403);
    }

    public function canEnter(User $user): bool
    {
        return $user->is_active && ($user->isSuperAdmin() || $this->schoolAdminIds($user)->isNotEmpty() || $this->available($user)->exists());
    }

    public function canDelegate(User $user, Activity $activity): bool
    {
        return $this->canManage($user, $activity) && ($user->isSuperAdmin() || $this->schoolAdminIds($user)->isNotEmpty());
    }

    public function reviewActivity(int $id, bool $approve, string $reason = ''): void
    {
        abort_unless(auth()->user()?->is_active && auth()->user()->isSuperAdmin(), 403);
        if (! $approve) {
            validator(['reason' => $reason], ['reason' => 'required|string|max:1000'])->validate();
        }
        DB::transaction(function () use ($id, $approve, $reason): void {
            $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->lockForUpdate()->findOrFail($id);
            if ($activity->approval_status !== 'pending') {
                throw ValidationException::withMessages(['review' => 'Pengajuan sudah diproses.']);
            }
            School::where('is_active', true)->findOrFail($activity->organizer_school_id);
            $activity->forceFill(['approval_status' => $approve ? 'approved' : 'rejected', 'status' => $approve ? 'published' : 'draft',
                'is_public' => $approve, 'published_at' => $approve ? now() : null, 'reviewed_by' => auth()->id(),
                'reviewed_at' => now(), 'rejection_reason' => $approve ? null : $reason])->save();
        });
    }

    public function delegate(Activity $activity, int $userId): ActivityDelegate
    {
        $actor = auth()->user();
        abort_unless($actor && $this->canDelegate($actor, $activity), 403);

        return DB::transaction(function () use ($activity, $userId, $actor): ActivityDelegate {
            $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->lockForUpdate()->findOrFail($activity->id);
            abort_unless($this->canDelegate($actor, $activity), 403);
            $recipient = User::where('is_active', true)->findOrFail($userId);
            $schoolIds = $this->schoolAdminIds($actor);
            $localSchoolId = $recipient->schoolMemberships()->whereIn('school_id', $schoolIds)->where('is_active', true)->whereNull('left_at')->value('school_id');
            $approved = $actor->isSuperAdmin() || $localSchoolId !== null;
            $delegation = ActivityDelegate::firstOrNew(['activity_id' => $activity->id, 'user_id' => $recipient->id]);
            if ($delegation->exists && in_array($delegation->status, ['approved', 'pending'], true)) {
                return $delegation;
            }
            $delegation->fill(['requested_by' => $actor->id, 'requesting_school_id' => $actor->isSuperAdmin() ? null : ($localSchoolId ?? $schoolIds->first()),
                'status' => $approved ? 'approved' : 'pending', 'reviewed_by' => $approved ? $actor->id : null,
                'reviewed_at' => $approved ? now() : null, 'review_note' => null])->save();

            return $delegation;
        });
    }

    public function reviewDelegate(int $id, bool $approve): void
    {
        abort_unless(auth()->user()?->is_active && auth()->user()->isSuperAdmin(), 403);
        DB::transaction(function () use ($id, $approve): void {
            $delegation = ActivityDelegate::lockForUpdate()->findOrFail($id);
            if ($delegation->status !== 'pending') {
                throw ValidationException::withMessages(['delegate' => 'Delegasi sudah diproses.']);
            }
            $activity = $delegation->activity;
            $requester = User::findOrFail($delegation->requested_by);
            abort_unless($activity && $this->canDelegate($requester, $activity), 403);
            User::where('is_active', true)->findOrFail($delegation->user_id);
            $delegation->update(['status' => $approve ? 'approved' : 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        });
    }

    public function revoke(Activity $activity, int $id): void
    {
        abort_unless(auth()->user() && $this->canDelegate(auth()->user(), $activity), 403);
        $activity->delegates()->findOrFail($id)->update(['status' => 'revoked', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
    }
}
