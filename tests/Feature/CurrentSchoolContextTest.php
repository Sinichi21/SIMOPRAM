<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentSchoolContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_without_active_school_uses_global_context(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' => 'super_admin',

                    'is_active' => true,
                ]);

        School::factory()
            ->create();

        $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            )
            ->assertOk();

        $this->assertNull(
            session(
                'active_school_id'
            )
        );

        $this->assertNull(
            app(
                SchoolContext::class
            )->id()
        );
    }

    public function test_super_admin_with_active_school_uses_school_context(): void
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

        $this->assertSame(
            $school->id,
            app(
                SchoolContext::class
            )->id()
        );
    }

    public function test_invalid_super_admin_school_session_returns_to_global_context(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' => 'super_admin',

                    'is_active' => true,
                ]);

        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' => 999999,
            ])
            ->get(
                route('dashboard')
            )
            ->assertOk();

        $this->assertNull(
            session(
                'active_school_id'
            )
        );

        $this->assertNull(
            app(
                SchoolContext::class
            )->id()
        );
    }
}
