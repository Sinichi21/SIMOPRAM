<?php

namespace App\Livewire\Student;

use App\Models\AcademicYear;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\ScoutUnitMember;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class ScoutProfile extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        abort_unless(
            $user?->hasRole('student'),
            403
        );

        $student = Student::query()
            ->where('user_id', $user->id)
            ->with('school')
            ->first();

        if (! $student) {
            return view(
                'livewire.student.scout-profile',
                $this->emptyData()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tahun ajaran aktif
        |--------------------------------------------------------------------------
        */

        $academicYear = AcademicYear::query()
            ->where('is_active', true)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Kelas aktif
        |--------------------------------------------------------------------------
        */

        $enrollment = $student
            ->enrollments()
            ->when(
                $academicYear,
                fn ($query) => $query->where(
                    'academic_year_id',
                    $academicYear->id
                )
            )
            ->where('status', 'active')
            ->with('classroom')
            ->latest('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Golongan Pramuka
        |--------------------------------------------------------------------------
        */

        $scoutLevelHistory = $student
            ->scoutLevelHistories()
            ->latest('id')
            ->first();

        $scoutLevel = null;

        if (
            $scoutLevelHistory
            &&
            $scoutLevelHistory->getAttribute(
                'scout_level_id'
            )
        ) {
            $scoutLevel = ScoutLevel::query()
                ->find(
                    $scoutLevelHistory
                        ->getAttribute(
                            'scout_level_id'
                        )
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Keanggotaan Regu / Barung
        |--------------------------------------------------------------------------
        |
        | Beberapa field keanggotaan dibuat optional karena
        | struktur project dapat berubah.
        |--------------------------------------------------------------------------
        */

        $membershipQuery = $student
            ->scoutUnitMembers()
            ->latest('id');

        if (
            Schema::hasColumn(
                'scout_unit_members',
                'is_active'
            )
        ) {
            $membershipQuery->where(
                'is_active',
                true
            );
        }

        if (
            Schema::hasColumn(
                'scout_unit_members',
                'left_at'
            )
        ) {
            $membershipQuery->whereNull(
                'left_at'
            );
        }

        $membership =
            $membershipQuery->first();

        $scoutUnit = null;

        if (
            $membership
            &&
            $membership->getAttribute(
                'scout_unit_id'
            )
        ) {
            $scoutUnit = ScoutUnit::query()
                ->find(
                    $membership->getAttribute(
                        'scout_unit_id'
                    )
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Anggota satu Regu / Barung
        |--------------------------------------------------------------------------
        */

        $members = collect();

        if ($scoutUnit) {
            $members = $this->unitMembers(
                $scoutUnit->id,
                $student->id
            );
        }

        $unitName = $this->unitName(
            $scoutUnit
        );

        $unitType = $this->unitType(
            $scoutLevel?->name
        );

        $memberRole = $this->roleLabel(
            $membership?->getAttribute(
                'role'
            )
        );

        return view(
            'livewire.student.scout-profile',
            [
                'student' => $student,
                'academicYear' => $academicYear,
                'enrollment' => $enrollment,

                'scoutLevelHistory' => $scoutLevelHistory,

                'scoutLevel' => $scoutLevel,

                'membership' => $membership,

                'scoutUnit' => $scoutUnit,

                'unitName' => $unitName,

                'unitType' => $unitType,

                'memberRole' => $memberRole,

                'members' => $members,
            ]
        );
    }

    private function unitMembers(
        int $scoutUnitId,
        int $currentStudentId
    ): Collection {
        $query = ScoutUnitMember::query()
            ->where(
                'scout_unit_id',
                $scoutUnitId
            );

        if (
            Schema::hasColumn(
                'scout_unit_members',
                'is_active'
            )
        ) {
            $query->where(
                'is_active',
                true
            );
        }

        if (
            Schema::hasColumn(
                'scout_unit_members',
                'left_at'
            )
        ) {
            $query->whereNull(
                'left_at'
            );
        }

        $memberships =
            $query->get();

        $students = Student::query()
            ->whereIn(
                'id',
                $memberships->pluck(
                    'student_id'
                )
            )
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        return $memberships
            ->map(
                function (
                    ScoutUnitMember $membership
                ) use (
                    $students,
                    $currentStudentId
                ): ?array {
                    $student = $students->get(
                        $membership->student_id
                    );

                    if (! $student) {
                        return null;
                    }

                    return [
                        'student' => $student,

                        'role' => $this->roleLabel(
                            $membership
                                ->getAttribute(
                                    'role'
                                )
                        ),

                        'isCurrentUser' => (int) $student->id
                            ===
                            $currentStudentId,
                    ];
                }
            )
            ->filter()
            ->sortBy(
                fn (array $item) => $item['student']->name
            )
            ->values();
    }

    private function unitName(
        ?ScoutUnit $unit
    ): ?string {
        if (! $unit) {
            return null;
        }

        return $unit->getAttribute('name')
            ?? $unit->getAttribute(
                'unit_name'
            )
            ?? $unit->getAttribute(
                'title'
            )
            ?? 'Regu / Barung #'.$unit->id;
    }

    private function unitType(
        ?string $scoutLevelName
    ): string {
        $level = mb_strtolower(
            (string) $scoutLevelName
        );

        if (str_contains($level, 'siaga')) {
            return 'Barung';
        }

        if (
            str_contains(
                $level,
                'penggalang'
            )
        ) {
            return 'Regu';
        }

        if (
            str_contains(
                $level,
                'penegak'
            )
        ) {
            return 'Sangga';
        }

        if (
            str_contains(
                $level,
                'pandega'
            )
        ) {
            return 'Reka';
        }

        return 'Regu / Barung';
    }

    private function roleLabel(
        mixed $role
    ): string {
        return match (
            mb_strtolower(
                trim(
                    (string) $role
                )
            )
        ) {
            'leader',
            'pinru',
            'pemimpin' => 'Pemimpin',

            'deputy',
            'wapinru',
            'wakil' => 'Wakil Pemimpin',

            'sulung' => 'Sulung',

            'member',
            'anggota',
            '' => 'Anggota',

            default => str(
                (string) $role
            )
                ->replace(
                    ['_', '-'],
                    ' '
                )
                ->title()
                ->toString(),
        };
    }

    private function emptyData(): array
    {
        return [
            'student' => null,
            'academicYear' => null,
            'enrollment' => null,
            'scoutLevelHistory' => null,
            'scoutLevel' => null,
            'membership' => null,
            'scoutUnit' => null,
            'unitName' => null,
            'unitType' => 'Regu / Barung',
            'memberRole' => null,
            'members' => collect(),
        ];
    }
}
