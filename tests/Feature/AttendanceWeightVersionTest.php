<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Services\AttendanceWeightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceWeightVersionTest extends TestCase
{
    use RefreshDatabase;


    public function test_default_configuration_uses_version_one(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' =>
                        'super_admin',

                    'is_active' =>
                        true,
                ]);


        $school =
            School::factory()
                ->create();


        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' =>
                    $school->id,
            ])
            ->get(
                route('dashboard')
            )
            ->assertOk();


        $service =
            app(
                AttendanceWeightService::class
            );


        $this->assertSame(
            1,
            $service->version()
        );
    }


    public function test_saving_default_weights_keeps_version_one(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' =>
                        'super_admin',

                    'is_active' =>
                        true,
                ]);


        $school =
            School::factory()
                ->create();


        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' =>
                    $school->id,
            ])
            ->get(
                route('dashboard')
            )
            ->assertOk();


        $service =
            app(
                AttendanceWeightService::class
            );


        $setting =
            $service
                ->savePercentages(
                    [
                        'present' => 100,
                        'late' => 75,
                        'sick' => 75,
                        'excused' => 75,
                        'absent' => 0,
                    ],
                    $user->id
                );


        $this->assertSame(
            1,
            $setting->version
        );
    }


    public function test_changing_weight_increments_configuration_version(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' =>
                        'super_admin',

                    'is_active' =>
                        true,
                ]);


        $school =
            School::factory()
                ->create();


        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' =>
                    $school->id,
            ])
            ->get(
                route('dashboard')
            )
            ->assertOk();


        $service =
            app(
                AttendanceWeightService::class
            );


        /*
        |--------------------------------------------------------------------------
        | Default = version 1
        |--------------------------------------------------------------------------
        */

        $service->savePercentages(
            [
                'present' => 100,
                'late' => 75,
                'sick' => 75,
                'excused' => 75,
                'absent' => 0,
            ],
            $user->id
        );


        /*
        |--------------------------------------------------------------------------
        | Ubah terlambat
        |--------------------------------------------------------------------------
        */

        $setting =
            $service
                ->savePercentages(
                    [
                        'present' => 100,
                        'late' => 60,
                        'sick' => 75,
                        'excused' => 75,
                        'absent' => 0,
                    ],
                    $user->id
                );


        $this->assertSame(
            2,
            $setting->version
        );


        /*
        |--------------------------------------------------------------------------
        | Simpan nilai sama → version tidak naik
        |--------------------------------------------------------------------------
        */

        $setting =
            $service
                ->savePercentages(
                    [
                        'present' => 100,
                        'late' => 60,
                        'sick' => 75,
                        'excused' => 75,
                        'absent' => 0,
                    ],
                    $user->id
                );


        $this->assertSame(
            2,
            $setting->version
        );


        /*
        |--------------------------------------------------------------------------
        | Ubah sakit → version 3
        |--------------------------------------------------------------------------
        */

        $setting =
            $service
                ->savePercentages(
                    [
                        'present' => 100,
                        'late' => 60,
                        'sick' => 50,
                        'excused' => 75,
                        'absent' => 0,
                    ],
                    $user->id
                );


        $this->assertSame(
            3,
            $setting->version
        );
    }


    public function test_configuration_version_is_isolated_per_school(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' =>
                        'super_admin',

                    'is_active' =>
                        true,
                ]);


        $schoolA =
            School::factory()
                ->create();


        $schoolB =
            School::factory()
                ->create();


        /*
        |--------------------------------------------------------------------------
        | Sekolah A
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' =>
                    $schoolA->id,
            ])
            ->get(
                route('dashboard')
            )
            ->assertOk();


        app(
            AttendanceWeightService::class
        )
            ->savePercentages(
                [
                    'present' => 100,
                    'late' => 50,
                    'sick' => 75,
                    'excused' => 75,
                    'absent' => 0,
                ],
                $user->id
            );


        $versionA =
            app(
                AttendanceWeightService::class
            )
                ->version();


        $this->assertSame(
            2,
            $versionA
        );


        /*
        |--------------------------------------------------------------------------
        | Sekolah B
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->post(
                route('school.switch'),
                [
                    'school_id' =>
                        $schoolB->id,
                ]
            )
            ->assertRedirect(
                route('dashboard')
            );


        $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            )
            ->assertOk();


        $versionB =
            app(
                AttendanceWeightService::class
            )
                ->version();


        $this->assertSame(
            1,
            $versionB
        );
    }
}