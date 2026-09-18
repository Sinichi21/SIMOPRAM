<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnnouncementAudienceService
{
    public function users(
        Announcement $announcement
    ): Collection {
        $announcement->loadMissing(
            'targets'
        );

        $userIds = collect();

        foreach (
            $announcement->targets as $target
        ) {
            $ids = match (
                $target->target_type
            ) {
                'all_students' => $this->allStudents(),

                'all_coaches' => $this->allCoaches(),

                'classroom' => $this->classroom(
                    (int) $target->target_id
                ),

                'scout_unit' => $this->scoutUnit(
                    (int) $target->target_id
                ),

                default => collect(),
            };

            $userIds = $userIds->merge(
                $ids
            );
        }

        return User::query()
            ->whereIn(
                'id',
                $userIds
                    ->filter()
                    ->unique()
                    ->values()
            )
            ->where(
                'is_active',
                true
            )
            ->get();
    }

    protected function allStudents(): Collection
    {
        return User::query()
            ->whereHas(
                'student',
                fn (Builder $query) => $query->where(
                    'status',
                    'active'
                )
            )
            ->pluck('id');
    }

    protected function allCoaches(): Collection
    {
        return User::query()
            ->whereHas(
                'coach',
                fn (Builder $query) => $query->where(
                    'is_active',
                    true
                )
            )
            ->pluck('id');
    }

    protected function classroom(
        int $classroomId
    ): Collection {
        return User::query()
            ->whereHas(
                'student',
                function (
                    Builder $studentQuery
                ) use (
                    $classroomId
                ): void {
                    $studentQuery
                        ->where(
                            'status',
                            'active'
                        )
                        ->whereHas(
                            'enrollments',
                            fn (
                                Builder $query
                            ) => $query
                                ->where(
                                    'classroom_id',
                                    $classroomId
                                )
                                ->where(
                                    'status',
                                    'active'
                                )
                        );
                }
            )
            ->pluck('id');
    }

    protected function scoutUnit(
        int $unitId
    ): Collection {
        return User::query()
            ->whereHas(
                'student',
                function (
                    Builder $studentQuery
                ) use (
                    $unitId
                ): void {
                    $studentQuery
                        ->where(
                            'status',
                            'active'
                        )
                        ->whereHas(
                            'scoutUnitMembers',
                            fn (
                                Builder $query
                            ) => $query
                                ->where(
                                    'scout_unit_id',
                                    $unitId
                                )
                                ->whereNull(
                                    'left_at'
                                )
                        );
                }
            )
            ->pluck('id');
    }

    public function publishedForUser(
        User $user
    ): Builder {
        $student =
            $user->student;

        $coach =
            $user->coach;

        $isStudent =
            $student?->status === 'active';

        $isCoach =
            (bool) $coach?->is_active;

        $classroomIds =
            $isStudent
                ? $student
                    ->enrollments()
                    ->where(
                        'status',
                        'active'
                    )
                    ->pluck(
                        'classroom_id'
                    )
                : collect();

        $scoutUnitIds =
            $isStudent
                ? $student
                    ->scoutUnitMembers()
                    ->whereNull(
                        'left_at'
                    )
                    ->pluck(
                        'scout_unit_id'
                    )
                : collect();

        return Announcement::query()

            ->where(
                'status',
                'published'
            )

            ->whereNotNull(
                'published_at'
            )

            ->where(
                'published_at',
                '<=',
                now()
            )

            ->where(
                function (
                    Builder $query
                ): void {
                    $query
                        ->whereNull(
                            'expires_at'
                        )
                        ->orWhere(
                            'expires_at',
                            '>',
                            now()
                        );
                }
            )

            ->whereHas(
                'targets',
                function (
                    Builder $targetQuery
                ) use (
                    $isStudent,
                    $isCoach,
                    $classroomIds,
                    $scoutUnitIds
                ): void {

                    $targetQuery->where(
                        function (
                            Builder $query
                        ) use (
                            $isStudent,
                            $isCoach,
                            $classroomIds,
                            $scoutUnitIds
                        ): void {

                            if ($isStudent) {

                                $query->where(
                                    function (
                                        Builder $studentQuery
                                    ) use (
                                        $classroomIds,
                                        $scoutUnitIds
                                    ): void {

                                        $studentQuery->where(
                                            'target_type',
                                            'all_students'
                                        );

                                        if (
                                            $classroomIds
                                                ->isNotEmpty()
                                        ) {
                                            $studentQuery
                                                ->orWhere(
                                                    function (
                                                        Builder $classQuery
                                                    ) use (
                                                        $classroomIds
                                                    ): void {

                                                        $classQuery
                                                            ->where(
                                                                'target_type',
                                                                'classroom'
                                                            )
                                                            ->whereIn(
                                                                'target_id',
                                                                $classroomIds
                                                            );
                                                    }
                                                );
                                        }

                                        if (
                                            $scoutUnitIds
                                                ->isNotEmpty()
                                        ) {
                                            $studentQuery
                                                ->orWhere(
                                                    function (
                                                        Builder $unitQuery
                                                    ) use (
                                                        $scoutUnitIds
                                                    ): void {

                                                        $unitQuery
                                                            ->where(
                                                                'target_type',
                                                                'scout_unit'
                                                            )
                                                            ->whereIn(
                                                                'target_id',
                                                                $scoutUnitIds
                                                            );
                                                    }
                                                );
                                        }
                                    }
                                );
                            }

                            if ($isCoach) {

                                if ($isStudent) {

                                    $query->orWhere(
                                        'target_type',
                                        'all_coaches'
                                    );

                                } else {

                                    $query->where(
                                        'target_type',
                                        'all_coaches'
                                    );
                                }
                            }

                            if (
                                ! $isStudent
                                &&
                                ! $isCoach
                            ) {
                                $query->whereRaw(
                                    '1 = 0'
                                );
                            }
                        }
                    );
                }
            );
    }
}
