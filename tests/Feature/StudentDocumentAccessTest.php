<?php

use App\Models\ReportVerification;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentDocumentAccessService;
use Database\Seeders\RolePermissionSeeder;

test(
    'student only sees documents assigned to them',
    function (): void {
        /*
        |--------------------------------------------------------------------------
        | Bootstrap role & permission
        |--------------------------------------------------------------------------
        |
        | Database test menggunakan RefreshDatabase sehingga tabel roles dan
        | permissions kosong pada awal test.
        |
        */

        $this->seed(
            RolePermissionSeeder::class
        );

        $school =
            School::factory()->create();

        /*
        |--------------------------------------------------------------------------
        | Aktifkan Spatie Team
        |--------------------------------------------------------------------------
        |
        | model_has_roles menggunakan school_id sebagai team key.
        |
        */

        setPermissionsTeamId(
            $school->id
        );

        $userOne =
            User::factory()->create();

        $userTwo =
            User::factory()->create();

        $userOne->assignRole(
            'student'
        );

        $userTwo->assignRole(
            'student'
        );

        $studentOne =
            Student::factory()->create([
                'school_id' => $school->id,

                'user_id' => $userOne->id,
            ]);

        $studentTwo =
            Student::factory()->create([
                'school_id' => $school->id,

                'user_id' => $userTwo->id,
            ]);

        $ownDocument =
            ReportVerification::query()
                ->create([
                    'school_id' => $school->id,

                    'code' => str_repeat(
                        'a',
                        48
                    ),

                    'document_type' => 'certificate',

                    'snapshot_checksum' => hash(
                        'sha256',
                        'own'
                    ),

                    'metadata' => [
                        'student_id' => $studentOne->id,
                    ],

                    'file_disk' => 'local',

                    'file_path' => 'test/own.pdf',

                    'archived_at' => now(),

                    'issued_at' => now(),

                    'verification_count' => 0,
                ]);

        ReportVerification::query()
            ->create([
                'school_id' => $school->id,

                'code' => str_repeat(
                    'b',
                    48
                ),

                'document_type' => 'certificate',

                'snapshot_checksum' => hash(
                    'sha256',
                    'other'
                ),

                'metadata' => [
                    'student_id' => $studentTwo->id,
                ],

                'file_disk' => 'local',

                'file_path' => 'test/other.pdf',

                'archived_at' => now(),

                'issued_at' => now(),

                'verification_count' => 0,
            ]);

        $documents =
            app(
                StudentDocumentAccessService::class
            )
                ->queryFor(
                    $userOne
                )
                ->get();

        expect($documents)
            ->toHaveCount(1)
            ->and(
                $documents->first()->id
            )
            ->toBe(
                $ownDocument->id
            );
    }
);
