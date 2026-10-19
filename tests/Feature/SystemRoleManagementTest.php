<?php

use App\Livewire\SystemRoleManagement\Index as SystemRoleManagementIndex;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Support\SchoolContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->school = School::factory()->create(['name' => 'Sekolah Satu']);
    $this->otherSchool = School::factory()->create(['name' => 'Sekolah Dua']);
    $this->superAdmin = User::factory()->create(['system_role' => 'super_admin']);
    $this->actingAs($this->superAdmin);
    app(SchoolContext::class)->set($this->school);
    setPermissionsTeamId($this->school->id);

    Permission::firstOrCreate(['name' => 'students.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'activities.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.manage', 'guard_name' => 'web']);
});

it('allows only super admins to access built in role settings', function (): void {
    $user = User::factory()->create(['system_role' => 'user']);

    $this->actingAs($user)
        ->get(route('system-roles.index'))
        ->assertForbidden();
});

it('opens the system role settings in global mode for super admins', function (): void {
    $this->get(route('system-roles.index'))
        ->assertOk()
        ->assertSee('Role bawaan sistem')
        ->assertSee('Super Admin');
});

it('shows built in roles and keeps super admin read only', function (): void {
    $editableRole = Role::create([
        'name' => 'school_admin',
        'guard_name' => 'web',
        'school_id' => null,
    ]);
    $superAdminRole = Role::create([
        'name' => 'super_admin',
        'guard_name' => 'web',
        'school_id' => null,
    ]);

    Livewire::test(SystemRoleManagementIndex::class)
        ->assertSee('Role bawaan sistem')
        ->assertSee('Admin Sekolah')
        ->assertSee('Super Admin')
        ->call('editSystemRole', $editableRole->id)
        ->assertSet('editingRoleId', $editableRole->id);

    expect(fn () => Livewire::test(SystemRoleManagementIndex::class)
        ->call('editSystemRole', $superAdminRole->id))
        ->toThrow(ModelNotFoundException::class);
});

it('updates permissions on a built in role without changing its name or scope', function (): void {
    $role = Role::create([
        'name' => 'school_admin',
        'guard_name' => 'web',
        'school_id' => null,
    ]);
    $role->syncPermissions(['students.view']);

    Livewire::test(SystemRoleManagementIndex::class)
        ->call('editSystemRole', $role->id)
        ->set('selectedPermissions', ['activities.view', 'roles.manage'])
        ->call('saveSystemRolePermissions')
        ->assertHasNoErrors()
        ->assertSee('Permission role bawaan berhasil diperbarui.');

    setPermissionsTeamId(null);
    $role->refresh();

    expect($role->name)->toBe('school_admin')
        ->and($role->school_id)->toBeNull()
        ->and($role->permissions()->pluck('name')->sort()->values()->all())
        ->toBe(['activities.view', 'roles.manage']);
});

it('preserves super admin permission changes when the role seeder runs again', function (): void {
    $seeder = new RolePermissionSeeder;
    $seeder->run();

    setPermissionsTeamId(null);
    $schoolAdminRole = Role::query()
        ->whereNull('school_id')
        ->where('name', 'school_admin')
        ->firstOrFail();
    $schoolAdminRole->syncPermissions(['students.view']);

    $seeder->run();
    $schoolAdminRole->refresh();

    expect($schoolAdminRole->permissions()->pluck('name')->all())
        ->toBe(['students.view']);
});

it('delegates and revokes school admin roles only for the selected school', function (): void {
    $user = User::factory()->create(['system_role' => 'user']);
    SchoolUserMembership::query()->create([
        'school_id' => $this->school->id,
        'user_id' => $user->id,
        'is_active' => true,
        'joined_at' => now()->toDateString(),
    ]);
    SchoolUserMembership::query()->create([
        'school_id' => $this->otherSchool->id,
        'user_id' => $user->id,
        'is_active' => true,
        'joined_at' => now()->toDateString(),
    ]);

    Role::create(['name' => 'school_admin', 'guard_name' => 'web', 'school_id' => null]);
    Role::create(['name' => 'scout_admin', 'guard_name' => 'web', 'school_id' => null]);
    Role::create(['name' => 'coach', 'guard_name' => 'web', 'school_id' => null]);

    setPermissionsTeamId($this->otherSchool->id);
    $user->assignRole('coach');
    setPermissionsTeamId($this->school->id);

    Livewire::test(SystemRoleManagementIndex::class)
        ->set('selectedSchoolId', $this->school->id)
        ->call('selectUser', $user->id)
        ->set('roleToDelegate', 'school_admin')
        ->call('delegateRole')
        ->set('roleToDelegate', 'scout_admin')
        ->call('delegateRole')
        ->assertHasNoErrors();

    setPermissionsTeamId($this->school->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');
    expect($user->hasRole('school_admin'))->toBeTrue()
        ->and($user->hasRole('scout_admin'))->toBeTrue();

    Livewire::test(SystemRoleManagementIndex::class)
        ->set('selectedSchoolId', $this->school->id)
        ->call('selectUser', $user->id)
        ->call('revokeRole', 'school_admin')
        ->assertHasNoErrors();

    $user->unsetRelation('roles')->unsetRelation('permissions');
    expect($user->hasRole('school_admin'))->toBeFalse()
        ->and($user->hasRole('scout_admin'))->toBeTrue();

    setPermissionsTeamId($this->otherSchool->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');
    expect($user->hasRole('coach'))->toBeTrue()
        ->and($user->hasRole('school_admin'))->toBeFalse()
        ->and($user->hasRole('scout_admin'))->toBeFalse();
});

it('does not delegate elevated roles to an account outside the selected school', function (): void {
    $user = User::factory()->create(['system_role' => 'user']);
    SchoolUserMembership::query()->create([
        'school_id' => $this->otherSchool->id,
        'user_id' => $user->id,
        'is_active' => true,
        'joined_at' => now()->toDateString(),
    ]);

    expect(fn () => Livewire::test(SystemRoleManagementIndex::class)
        ->set('selectedSchoolId', $this->school->id)
        ->call('selectUser', $user->id))
        ->toThrow(ModelNotFoundException::class);
});
