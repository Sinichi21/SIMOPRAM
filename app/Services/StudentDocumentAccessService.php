<?php

namespace App\Services;

use App\Models\ReportVerification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentDocumentAccessService
{
    public function student(User $user): ?Student
    {
        return Student::query()
            ->where('user_id', $user->id)
            ->first();
    }

    public function queryFor(User $user): Builder
    {
        abort_unless(
            $user->hasRole('student'),
            403
        );

        $student = $this->student($user);

        if (! $student) {
            /*
            |--------------------------------------------------------------------------
            | Query yang pasti kosong
            |--------------------------------------------------------------------------
            */
            return ReportVerification::query()
                ->whereRaw('1 = 0');
        }

        return ReportVerification::query()
            ->where(
                'school_id',
                $student->school_id
            )
            ->whereNotNull(
                'archived_at'
            )
            ->where(
                function (Builder $query) use (
                    $student,
                    $user
                ): void {
                    /*
                    |--------------------------------------------------------------------------
                    | Satu siswa
                    |--------------------------------------------------------------------------
                    */

                    $query->where(
                        'metadata->student_id',
                        $student->id
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Banyak siswa
                    |--------------------------------------------------------------------------
                    */

                    $query->orWhereJsonContains(
                        'metadata->student_ids',
                        $student->id
                    );

                    $query->orWhereJsonContains(
                        'metadata->student_ids',
                        (string) $student->id
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Satu user
                    |--------------------------------------------------------------------------
                    */

                    $query->orWhere(
                        'metadata->recipient_user_id',
                        $user->id
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Banyak user
                    |--------------------------------------------------------------------------
                    */

                    $query->orWhereJsonContains(
                        'metadata->recipient_user_ids',
                        $user->id
                    );

                    $query->orWhereJsonContains(
                        'metadata->recipient_user_ids',
                        (string) $user->id
                    );
                }
            );
    }

    public function findForUser(
        User $user,
        string $code
    ): ReportVerification {
        abort_unless(
            preg_match(
                '/^[a-f0-9]{48}$/',
                strtolower($code)
            ) === 1,
            404
        );

        return $this
            ->queryFor($user)
            ->with([
                'school',
                'closure.academicYear',
                'closure.semester',
                'issuer',
            ])
            ->where(
                'code',
                strtolower($code)
            )
            ->firstOrFail();
    }

    public function countFor(
        User $user
    ): int {
        return $this
            ->queryFor($user)
            ->whereNull('revoked_at')
            ->count();
    }
}
