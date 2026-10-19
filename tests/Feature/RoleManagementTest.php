<?php

use App\Livewire\RoleManagement\Index as RoleManagementIndex;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->school = School::factory()->create();
    $this->user = User::factory()->create(['system_role' => 'super_admin']);
    $this->actingAs($this->user);
    app(SchoolContext::class)->set($this->school);
    setPermissionsTeamId($this->school->id);

    Permission::firstOrCreate(['name' => 'students.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'students.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'roles.view', 'guard_name' => 'web']);
});

it('creates a school-scoped role with selected permissions', function (): void {
    Livewire::test(RoleManagementIndex::class)
        ->call('startCreate')
        ->set('name', 'operator_absensi')
        ->set('selectedPermissions', ['students.view', 'students.create'])
        ->call('saveRole')
        ->assertHasNoErrors()
        ->assertSee('Role khusus sekolah');

    $role = Role::query()
        ->where('school_id', $this->school->id)
        ->where('name', 'operator_absensi')
        ->firstOrFail();

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['students.create', 'students.view']);
});

it('edits a role and synchronizes its permissions', function (): void {
    $role = Role::create([
        'name' => 'operator_lama',
        'guard_name' => 'web',
        'school_id' => $this->school->id,
    ]);
    $role->syncPermissions(['students.view']);

    Livewire::test(RoleManagementIndex::class)
        ->call('editRole', $role->id)
        ->set('name', 'operator_baru')
        ->set('selectedPermissions', ['students.create'])
        ->call('saveRole')
        ->assertHasNoErrors();

    $role->refresh();

    expect($role->name)->toBe('operator_baru')
        ->and($role->permissions->pluck('name')->all())->toBe(['students.create']);
});

it('does not allow assigning role management permissions to a school role', function (): void {
    Livewire::test(RoleManagementIndex::class)
        ->call('startCreate')
        ->set('name', 'operator_akses')
        ->set('selectedPermissions', ['roles.manage'])
        ->call('saveRole')
        ->assertHasErrors(['selectedPermissions.0']);
});

it('does not expose global system roles for editing', function (): void {
    $globalRole = Role::create([
        'name' => 'coach',
        'guard_name' => 'web',
        'school_id' => null,
    ]);

    Livewire::test(RoleManagementIndex::class)
        ->call('editRole', $globalRole->id)
        ->assertStatus(404);
});
