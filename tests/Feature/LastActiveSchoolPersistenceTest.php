<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LastActiveSchoolPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_restores_selected_school_after_session_is_lost(): void
    {
        $user = User::factory()->create(['system_role' => 'super_admin']);
        $school = School::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('school.switch'), ['school_id' => $school->id])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($school->id, $user->fresh()->last_active_school_id);

        $this->withSession(['active_school_id' => null])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($school->name);

        $this->assertSame($school->id, (int) session('active_school_id'));
        $this->assertSame($school->id, app(SchoolContext::class)->id());
    }

    public function test_explicit_global_choice_does_not_restore_previous_school(): void
    {
        $user = User::factory()->create(['system_role' => 'super_admin']);
        $school = School::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('school.switch'), ['school_id' => $school->id])
            ->assertRedirect(route('dashboard'));

        $this->post(route('school.switch'), ['school_id' => 'global'])
            ->assertRedirect(route('dashboard'));

        $this->assertNull($user->fresh()->last_active_school_id);

        $this->withSession(['active_school_id' => null])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Global SIMPRAM');

        $this->assertNull(session('active_school_id'));
    }

    public function test_school_member_restores_only_current_authorized_school(): void
    {
        $user = User::factory()->create(['system_role' => null]);
        $school = School::factory()->create(['is_active' => true]);
        SchoolUserMembership::query()->create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'is_active' => true,
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('school.switch'), ['school_id' => $school->id])
            ->assertRedirect(route('dashboard'));

        $this->withSession(['active_school_id' => null])
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame($school->id, (int) session('active_school_id'));
        $this->assertSame($school->id, $user->fresh()->last_active_school_id);
    }

    public function test_inactive_or_revoked_school_is_never_restored_from_preference(): void
    {
        $user = User::factory()->create(['system_role' => null]);
        $school = School::factory()->create(['is_active' => true]);
        $membership = SchoolUserMembership::query()->create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'is_active' => true,
            'joined_at' => now(),
            'left_at' => null,
        ]);
        $user->last_active_school_id = $school->id;
        $user->save();
        $membership->update(['is_active' => false]);

        $this->actingAs($user)
            ->withSession(['active_school_id' => null])
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertNull($user->fresh()->last_active_school_id);
        $this->assertNull(session('active_school_id'));
        $this->assertNull(app(SchoolContext::class)->id());
    }

    public function test_inactive_school_is_not_restored_for_super_admin(): void
    {
        $user = User::factory()->create(['system_role' => 'super_admin']);
        $school = School::factory()->inactive()->create();
        $user->last_active_school_id = $school->id;
        $user->save();

        $this->actingAs($user)
            ->withSession(['active_school_id' => null])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Global SIMPRAM');

        $this->assertNull($user->fresh()->last_active_school_id);
        $this->assertNull(session('active_school_id'));
    }
}
