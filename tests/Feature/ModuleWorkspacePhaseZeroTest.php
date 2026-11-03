<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Support\ModuleWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleWorkspacePhaseZeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_requires_authentication(): void
    {
        $this->get(route('modules.portal'))->assertRedirect();
    }

    public function test_unknown_module_returns_not_found(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['system_role' => 'super_admin']);

        $this->actingAs($user)
            ->withSession(['active_school_id' => $school->id])
            ->get(route('modules.show', 'tidak-ada'))
            ->assertNotFound();
    }

    public function test_user_without_module_permission_cannot_open_module(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['system_role' => 'user']);
        SchoolUserMembership::query()->create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_school_id' => $school->id])
            ->get(route('modules.show', 'inventaris'))
            ->assertForbidden();
    }

    public function test_module_links_are_filtered_by_permission(): void
    {
        $user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'inventory.view', 'guard_name' => 'web']);

        $module = ModuleWorkspace::find('inventaris');

        $this->assertNotNull($module);
        $this->assertFalse(ModuleWorkspace::permitted($user, $module));
        $this->assertSame([], ModuleWorkspace::accessibleLinks($user, $module));
    }
}
