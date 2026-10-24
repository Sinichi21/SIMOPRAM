<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ActivityAssignmentFilter
{
    public function defaultMode(User $user): string
    {
        return $user->isSystemAdmin() || $user->system_role === 'school_admin' || $user->hasRole('school_admin')
            ? 'all'
            : 'mine';
    }

    /** @return Collection<int, Coach> */
    public function coaches(int $schoolId): Collection
    {
        return Coach::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function apply(Builder $query, User $user, int $schoolId, string $mode, ?int $coachId): Builder
    {
        if ($mode === 'all') {
            return $query;
        }

        $assignedCoachId = $mode === 'coach'
            ? $coachId
            : Coach::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->value('id');

        if (! $assignedCoachId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('coaches', function (Builder $coachQuery) use ($assignedCoachId, $schoolId): void {
            $coachQuery
                ->where('coaches.id', $assignedCoachId)
                ->where('coaches.school_id', $schoolId)
                ->where('coaches.is_active', true);
        });
    }
}
