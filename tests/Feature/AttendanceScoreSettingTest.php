<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Services\AttendanceWeightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceScoreSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_weights_are_used_when_school_has_no_configuration(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' => 'super_admin',

                    'is_active' => true,
                ]);

        $school =
            School::factory()
                ->create();

        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' => $school->id,
            ])
            ->get(
                route('dashboard')
            )
            ->assertOk();

        $weights =
            app(
                AttendanceWeightService::class
            )->percentages();

        $this->assertSame(
            100.0,
            $weights['present']
        );

        $this->assertSame(
            75.0,
            $weights['late']
        );

        $this->assertSame(
            75.0,
            $weights['sick']
        );

        $this->assertSame(
            75.0,
            $weights['excused']
        );

        $this->assertSame(
            0.0,
            $weights['absent']
        );
    }

    public function test_attendance_weights_follow_active_school(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' => 'super_admin',

                    'is_active' => true,
                ]);

        $schoolA =
            School::factory()
                ->create([
                    'name' => 'Sekolah A',
                ]);

        $schoolB =
            School::factory()
                ->create([
                    'name' => 'Sekolah B',
                ]);

        DB::table(
            'attendance_score_settings'
        )->insert([
            [
                'school_id' => $schoolA->id,

                'present_weight' => 100,

                'late_weight' => 80,

                'sick_weight' => 60,

                'excused_weight' => 70,

                'absent_weight' => 0,

                'created_at' => now(),

                'updated_at' => now(),
            ],

            [
                'school_id' => $schoolB->id,

                'present_weight' => 100,

                'late_weight' => 50,

                'sick_weight' => 50,

                'excused_weight' => 50,

                'absent_weight' => 10,

                'created_at' => now(),

                'updated_at' => now(),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | SEKOLAH A
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' => $schoolA->id,
            ])
            ->get(
                route('dashboard')
            )
            ->assertOk();

        $weightsA =
            app(
                AttendanceWeightService::class
            )->percentages();

        $this->assertSame(
            80.0,
            $weightsA['late']
        );

        $this->assertSame(
            60.0,
            $weightsA['sick']
        );

        /*
        |--------------------------------------------------------------------------
        | SWITCH KE SEKOLAH B
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->post(
                route('school.switch'),
                [
                    'school_id' => $schoolB->id,
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

        /*
        |--------------------------------------------------------------------------
        | Resolve service baru agar tidak memakai cache request sebelumnya.
        |--------------------------------------------------------------------------
        */

        $weightsB =
            app(
                AttendanceWeightService::class
            )->percentages();

        $this->assertSame(
            50.0,
            $weightsB['late']
        );

        $this->assertSame(
            10.0,
            $weightsB['absent']
        );
    }

    public function test_global_super_admin_cannot_open_attendance_scoring_settings(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' => 'super_admin',

                    'is_active' => true,
                ]);

        $this
            ->actingAs($user)
            ->get(
                route(
                    'settings.attendance-scoring'
                )
            )
            ->assertStatus(
                409
            );
    }
}
