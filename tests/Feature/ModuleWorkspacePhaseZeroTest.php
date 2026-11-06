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
        $user = User::factory()->create();
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
    public function test_legacy_finance_route_resolves_to_the_finance_workspace(): void
    {
        $request = \Illuminate\Http\Request::create('/administrasi/keuangan-sarana', 'GET');
        $route = $this->app['router']->getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $this->assertSame('keuangan', ModuleWorkspace::activeSlug());
    }

    public function test_finance_is_a_standalone_module_without_administration_duplicate(): void
    {
        $finance = ModuleWorkspace::find('keuangan');
        $administration = ModuleWorkspace::find('administrasi');

        $this->assertNotNull($finance);
        $this->assertNotNull($administration);
        $this->assertContains('gudep.finance.view', $finance['permissions']);
        $this->assertSame('finance.index', $finance['links'][0]['route']);
        $this->assertNotContains('gudep.finance.view', $administration['permissions']);
        $this->assertNotContains(
            'administration.finance',
            array_column($administration['links'], 'route')
        );
        $this->assertSame(
            route('finance.index', absolute: false),
            '/keuangan'
        );
        $this->assertSame(
            route('administration.finance', absolute: false),
            '/administrasi/keuangan-sarana'
        );
    }
}
