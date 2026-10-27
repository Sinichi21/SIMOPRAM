<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\Student;
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
        // Student accounts are linked to Student records, not Coach records.
        // Only expose activities matching the student's active scout level or
        // activities for which the student is an explicit attendance participant.
        $student = Student::query()
            ->where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($student) {
            $levelIds = $student->scoutLevelHistories()
                ->where('is_active', true)
                ->pluck('scout_level_id');

            return $query->where(function (Builder $studentQuery) use ($student, $levelIds): void {
                if ($levelIds->isNotEmpty()) {
                    $studentQuery->whereHas('scoutLevels', function (Builder $levelQuery) use ($levelIds): void {
                        $levelQuery->whereIn('scout_levels.id', $levelIds);
                    });
                }

                $studentQuery->orWhereHas('attendanceSessions.participants', function (Builder $participantQuery) use ($student): void {
                    $participantQuery->where('student_id', $student->id);
                });
            });
        }

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
