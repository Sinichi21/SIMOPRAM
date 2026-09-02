<?php

namespace App\Services;

use App\Models\SchoolUserMembership;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentAccountService
{
    public function createAccount(
        Student $student,
        string $email,
        string $password
    ): User {
        $schoolId = app(
            SchoolContext::class
        )->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        if ($student->user_id) {
            throw ValidationException::withMessages([
                'email' =>
                    'Siswa ini sudah memiliki akun.',
            ]);
        }

        if (
            User::query()
                ->where('email', $email)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'email' =>
                    'Email tersebut sudah digunakan.',
            ]);
        }

        return DB::transaction(
            function () use (
                $student,
                $schoolId,
                $email,
                $password
            ): User {

                /*
                |--------------------------------------------------------------------------
                | User
                |--------------------------------------------------------------------------
                */

                $user = User::query()->create([
                    'name' =>
                        $student->name,

                    'email' =>
                        strtolower(trim($email)),

                    'password' =>
                        Hash::make($password),

                    'is_active' =>
                        true,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Membership Sekolah
                |--------------------------------------------------------------------------
                */

                SchoolUserMembership::query()
                    ->updateOrCreate(
                        [
                            'school_id' =>
                                $schoolId,

                            'user_id' =>
                                $user->id,
                        ],
                        [
                            'is_active' =>
                                true,

                            'joined_at' =>
                                now()->toDateString(),

                            'left_at' =>
                                null,
                        ]
                    );


                /*
                |--------------------------------------------------------------------------
                | Spatie Role: student
                |--------------------------------------------------------------------------
                */

                $previousTeamId =
                    getPermissionsTeamId();

                try {

                    setPermissionsTeamId(
                        $schoolId
                    );

                    $user
                        ->unsetRelation('roles')
                        ->unsetRelation('permissions');

                    $user->assignRole(
                        'student'
                    );

                } finally {

                    setPermissionsTeamId(
                        $previousTeamId
                    );

                    auth()->user()
                        ?->unsetRelation('roles')
                        ?->unsetRelation('permissions');
                }


                /*
                |--------------------------------------------------------------------------
                | Hubungkan User dengan Student
                |--------------------------------------------------------------------------
                */

                $student->update([
                    'user_id' =>
                        $user->id,
                ]);

                return $user;
            }
        );
    }


    public function resetPassword(
        Student $student,
        string $password
    ): void {
        if (! $student->user_id) {
            throw ValidationException::withMessages([
                'password' =>
                    'Siswa belum memiliki akun.',
            ]);
        }

        $user = User::query()
            ->findOrFail(
                $student->user_id
            );

        $user->update([
            'password' =>
                Hash::make($password),
        ]);
    }
}