<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Support\ModuleWorkspace;
use App\Support\SchoolContext;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
        app(SchoolContext::class)->set(School::factory()->create());
        Permission::firstOrCreate(['name' => 'inventory.view', 'guard_name' => 'web']);

        $module = ModuleWorkspace::find('inventaris');

        $this->assertNotNull($module);
        $this->assertFalse(ModuleWorkspace::permitted($user, $module));
        $this->assertSame([], ModuleWorkspace::accessibleLinks($user, $module));
    }

    public function test_legacy_finance_route_resolves_to_the_finance_workspace(): void
    {
        $request = Request::create('/administrasi/keuangan-sarana', 'GET');
        $route = $this->app['router']->getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $this->assertSame('keuangan', ModuleWorkspace::activeSlug());
    }

    public function test_module_owned_navigation_is_available_from_the_correct_workspace(): void
    {
        $activities = ModuleWorkspace::find('kegiatan');
        $administration = ModuleWorkspace::find('administrasi');

        $this->assertNotNull($activities);
        $this->assertNotNull($administration);

        $activityRoutes = array_column($activities['links'], 'route');
        $administrationRoutes = array_column($administration['links'], 'route');

        $this->assertContains('activities.index', $activityRoutes);
        $this->assertContains('attendances.index', $activityRoutes);
        $this->assertContains('journals.index', $activityRoutes);
        $this->assertContains('announcements.index', $activityRoutes);
        $this->assertContains('letters.incoming', $administrationRoutes);
        $this->assertContains('letters.outgoing', $administrationRoutes);
        $this->assertContains('letters.templates', $administrationRoutes);
    }

    public function test_program_planner_and_competency_tracking_have_distinct_navigation_labels(): void
    {
        $activities = collect(ModuleWorkspace::find('kegiatan')['links'])->keyBy('route');
        $assessment = collect(ModuleWorkspace::find('penilaian')['links'])->keyBy('route');

        $this->assertSame('Program Kerja Tahunan/Semester/Mingguan', $activities['administration.programs']['label']);
        $this->assertSame('Pembinaan SKU/SKK & Prestasi', $assessment['administration.development']['label']);
    }

    public function test_finance_is_a_standalone_module_without_administration_duplicate(): void
    {
        $finance = ModuleWorkspace::find('keuangan');
        $administration = ModuleWorkspace::find('administrasi');

        $this->assertNotNull($finance);
        $this->assertNotNull($administration);
        $this->assertSame('gudep.finance.view', $finance['links'][0]['permission']);
        $this->assertSame('finance.index', $finance['links'][0]['route']);
        $this->assertNotContains('gudep.finance.view', array_column($administration['links'], 'permission'));
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

    public function test_main_sidebar_links_to_modules_and_each_operational_destination_has_one_owner(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['system_role' => 'super_admin']);
        $this->actingAs($user)->withSession(['active_school_id' => $school->id]);

        $mainSidebar = $this->sidebar($this->get(route('modules.portal'))->assertOk());
        $destinations = [];

        foreach (['keanggotaan', 'kegiatan', 'penilaian', 'administrasi', 'keuangan', 'inventaris', 'laporan', 'sistem'] as $slug) {
            $moduleUrl = route('modules.show', $slug);
            $this->assertSame(1, $mainSidebar->query("//ui-sidebar//a[@href='$moduleUrl']")->length);
            $workspace = $this->get($moduleUrl)->assertOk()->assertSee('data-module="'.$slug.'"', false);
            $workspaceSidebar = $this->sidebar($workspace);

            foreach (ModuleWorkspace::find($slug)['links'] as $link) {
                $parameters = $link['route'] === 'settings.tenant-content' ? ['school' => $school] : [];
                $url = route($link['route'], $parameters);
                $this->assertNotContains($url, $destinations);
                $this->assertSame(0, $mainSidebar->query("//ui-sidebar//a[@href='$url']")->length);
                $this->assertSame(1, $workspaceSidebar->query("//ui-sidebar//a[@href='$url']")->length);
                $destinations[] = $url;
            }
        }
    }

    #[DataProvider('staffRoles')]
    public function test_staff_navigation_follows_permissions_in_the_selected_school(string $roleName): void
    {
        $school = School::factory()->create(['name' => 'Pangkalan Pertama']);
        $otherSchool = School::factory()->create(['name' => 'Pangkalan Kedua']);
        $user = User::factory()->create();
        $this->assignSchoolRole($user, $school, $roleName, ['reports.attendance.view']);
        $this->assignSchoolRole($user, $otherSchool, $roleName, ['inventory.view']);

        $this->actingAs($user)->withSession(['active_school_id' => $school->id])
            ->get(route('modules.portal'))->assertOk()
            ->assertSee(route('modules.show', 'laporan'))
            ->assertDontSee(route('modules.show', 'inventaris'));

        $this->get(route('modules.show', 'laporan'))->assertOk()
            ->assertSee('data-module="laporan"', false)
            ->assertSee(route('reports.attendance'))
            ->assertDontSee(route('reports.grades'))
            ->assertDontSee(route('reports.published-documents.index'));
        $this->get(route('reports.grades'))->assertForbidden();

        $this->post(route('school.switch'), ['school_id' => $otherSchool->id])
            ->assertRedirect(route('dashboard'))->assertSessionHas('active_school_id', $otherSchool->id);

        $this->get(route('inventory.index'))->assertOk()
            ->assertSee('data-module="inventaris"', false)
            ->assertSee('Pangkalan Kedua')
            ->assertDontSee(route('modules.show', 'laporan'));
        $this->get(route('modules.show', 'laporan'))->assertForbidden();
    }

    public static function staffRoles(): array
    {
        return ['admin sekolah' => ['school_admin'], 'pembina' => ['coach']];
    }

    public function test_super_admin_can_open_global_workspaces_without_selecting_a_school(): void
    {
        $user = User::factory()->create(['system_role' => 'super_admin']);
        $this->actingAs($user)->get(route('modules.portal'))->assertOk()
            ->assertSee(route('modules.show', 'sistem'))
            ->assertDontSee(route('modules.show', 'inventaris'));

        $this->get(route('modules.show', 'sistem'))->assertOk()
            ->assertSee(route('schools.index'))
            ->assertSee(route('system-roles.index'))
            ->assertDontSee(route('roles.index'));
        $this->get(route('schools.index'))->assertOk()->assertSee('data-module="sistem"', false);
        $this->get(route('admin.public-activities'))->assertOk()->assertSee('data-module="kegiatan"', false);
        $this->get(route('modules.show', 'inventaris'))->assertConflict();
    }

    public function test_bookmarks_keep_the_same_current_item_across_index_create_edit_and_detail_pages(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['system_role' => 'super_admin']);
        app(SchoolContext::class)->set($school);
        $announcement = Announcement::query()->create([
            'title' => 'Pengumuman Pengujian',
            'body' => 'Isi pengumuman',
            'created_by' => $user->id,
            'status' => 'draft',
        ]);
        $this->actingAs($user)->withSession(['active_school_id' => $school->id]);

        foreach ([
            ['announcements.index', [], 'kegiatan', 'announcements.index'],
            ['announcements.create', [], 'kegiatan', 'announcements.index'],
            ['announcements.edit', [$announcement->id], 'kegiatan', 'announcements.index'],
            ['reports.attendance.detail', [], 'laporan', 'reports.attendance'],
            ['administration.finance', [], 'keuangan', 'finance.index'],
            ['administration.programs', [], 'kegiatan', 'administration.programs'],
            ['inventory.loans.index', [], 'inventaris', 'inventory.loans.index'],
        ] as [$routeName, $parameters, $slug, $menuRoute]) {
            $response = $this->get(route($routeName, $parameters))->assertOk()->assertSee('data-module="'.$slug.'"', false);
            $currentUrl = route($menuRoute);
            $this->assertSame(1, $this->sidebar($response)->query("//ui-sidebar//a[@href='$currentUrl' and @data-current]")->length);
        }
    }

    public function test_student_keeps_personal_navigation_even_with_an_operational_permission(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        $this->assignSchoolRole($user, $school, 'student', ['inventory.view', 'attendances.self']);

        $this->actingAs($user)->withSession(['active_school_id' => $school->id])
            ->get(route('dashboard'))->assertOk()
            ->assertSee(route('attendances.self'))
            ->assertSee(route('student.grades'))
            ->assertSee(route('student.skills'))
            ->assertSee(route('student.documents'))
            ->assertDontSee(route('modules.portal'))
            ->assertDontSee(route('modules.show', 'inventaris'));
    }

    private function assignSchoolRole(User $user, School $school, string $roleName, array $permissions): void
    {
        $user->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true, 'joined_at' => today()]);
        setPermissionsTeamId($school->id);
        $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'school_id' => $school->id]);
        $role->syncPermissions(array_map(
            fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'),
            $permissions,
        ));
        $user->assignRole($role);
    }

    private function sidebar(TestResponse $response): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);

        return new DOMXPath($document);
    }
}
