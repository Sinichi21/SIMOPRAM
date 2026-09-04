<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalDashboardTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    protected function makeSuperAdmin(): User
    {
        return User::factory()
            ->create([
                'system_role' => 'super_admin',

                'is_active' => true,
            ]);
    }

    protected function makeRegularUser(): User
    {
        return User::factory()
            ->create([
                'system_role' => null,

                'is_active' => true,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Super Admin Global Dashboard
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_without_active_school_can_see_global_dashboard(): void
    {
        $user =
            $this->makeSuperAdmin();

        School::factory()
            ->count(2)
            ->create();

        $response =
            $this
                ->actingAs($user)
                ->get(
                    route('dashboard')
                );

        $response
            ->assertOk()
            ->assertSee(
                'Dashboard Global SIMPRAM'
            )
            ->assertSee(
                'Semua Sekolah'
            )
            ->assertSee(
                'data-school-menu',
                false
            )
            ->assertSee(
                'Pilih sekolah aktif terlebih dahulu.'
            )
            ->assertSee(
                'ui-disclosure',
                false
            );

        $this->assertNull(
            session(
                'active_school_id'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Super Admin masuk ke sekolah
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_switch_from_global_to_school(): void
    {
        $user =
            $this->makeSuperAdmin();

        $school =
            School::factory()
                ->create([
                    'name' => 'SD Test A',

                    'is_active' => true,
                ]);

        $response =
            $this
                ->actingAs($user)
                ->post(
                    route(
                        'school.switch'
                    ),
                    [
                        'school_id' => $school->id,
                    ]
                );

        $response
            ->assertRedirect(
                route('dashboard')
            );

        $response
            ->assertSessionHas(
                'active_school_id',
                $school->id
            );

        /*
        |--------------------------------------------------------------------------
        | Request berikutnya harus memakai Dashboard Sekolah
        |--------------------------------------------------------------------------
        */

        $dashboard =
            $this
                ->actingAs($user)
                ->get(
                    route('dashboard')
                );

        $dashboard
            ->assertOk()
            ->assertSee(
                'Dashboard Gugus Depan'
            )
            ->assertSee(
                'SD Test A'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Super Admin kembali ke Global
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_return_from_school_to_global_dashboard(): void
    {
        $user =
            $this->makeSuperAdmin();

        $school =
            School::factory()
                ->create();

        /*
        |--------------------------------------------------------------------------
        | Awalnya berada pada sekolah
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' => $school->id,
            ])
            ->get(
                route('dashboard')
            )
            ->assertOk();

        /*
        |--------------------------------------------------------------------------
        | Switch kembali ke global
        |--------------------------------------------------------------------------
        */

        $response =
            $this
                ->actingAs($user)
                ->withSession([
                    'active_school_id' => $school->id,
                ])
                ->post(
                    route(
                        'school.switch'
                    ),
                    [
                        'school_id' => 'global',
                    ]
                );

        $response
            ->assertRedirect(
                route('dashboard')
            );

        $response
            ->assertSessionMissing(
                'active_school_id'
            );

        /*
        |--------------------------------------------------------------------------
        | Dashboard berikutnya Global
        |--------------------------------------------------------------------------
        */

        $dashboard =
            $this
                ->actingAs($user)
                ->get(
                    route('dashboard')
                );

        $dashboard
            ->assertOk()
            ->assertSee(
                'Dashboard Global SIMPRAM'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | User biasa tidak boleh Global
    |--------------------------------------------------------------------------
    */

    public function test_regular_user_cannot_switch_to_global_mode(): void
    {
        $user =
            $this->makeRegularUser();

        $response =
            $this
                ->actingAs($user)
                ->post(
                    route(
                        'school.switch'
                    ),
                    [
                        'school_id' => 'global',
                    ]
                );

        $response
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | User biasa tidak boleh sekolah tanpa membership
    |--------------------------------------------------------------------------
    */

    public function test_regular_user_cannot_switch_to_school_without_membership(): void
    {
        $user =
            $this->makeRegularUser();

        $school =
            School::factory()
                ->create();

        $response =
            $this
                ->actingAs($user)
                ->post(
                    route(
                        'school.switch'
                    ),
                    [
                        'school_id' => $school->id,
                    ]
                );

        $response
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | User dengan membership boleh masuk sekolah
    |--------------------------------------------------------------------------
    */

    public function test_regular_user_can_switch_to_school_with_active_membership(): void
    {
        $user =
            $this->makeRegularUser();

        $school =
            School::factory()
                ->create();

        SchoolUserMembership::query()
            ->create([
                'school_id' => $school->id,

                'user_id' => $user->id,

                'is_active' => true,

                'joined_at' => now(),

                'left_at' => null,
            ]);

        $response =
            $this
                ->actingAs($user)
                ->post(
                    route(
                        'school.switch'
                    ),
                    [
                        'school_id' => $school->id,
                    ]
                );

        $response
            ->assertRedirect(
                route('dashboard')
            );

        $response
            ->assertSessionHas(
                'active_school_id',
                $school->id
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Sekolah nonaktif tidak bisa dipilih
    |--------------------------------------------------------------------------
    */

    public function test_inactive_school_cannot_be_selected(): void
    {
        $user =
            $this->makeSuperAdmin();

        $school =
            School::factory()
                ->inactive()
                ->create();

        $response =
            $this
                ->actingAs($user)
                ->post(
                    route(
                        'school.switch'
                    ),
                    [
                        'school_id' => $school->id,
                    ]
                );

        $response
            ->assertNotFound();
    }
}
