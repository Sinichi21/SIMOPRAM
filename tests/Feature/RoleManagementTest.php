<?php

use App\Livewire\RoleManagement\Index as RoleManagementIndex;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    expect(fn () => Livewire::test(RoleManagementIndex::class)
        ->call('editRole', $globalRole->id))
        ->toThrow(ModelNotFoundException::class);
});

it('shows system roles as read only and lists school custom roles separately', function (): void {
    Role::create([
        'name' => 'coach',
        'guard_name' => 'web',
        'school_id' => null,
    ]);

    Livewire::test(RoleManagementIndex::class)
        ->assertSee('Role bawaan sistem')
        ->assertSee('Pembina')
        ->assertSee('Belum ada role khusus sekolah.')
        ->assertDontSee('Edit / Atur Permission');
});

it('delegates assignable roles only to an active member of the current school', function (): void {
    $user = User::factory()->create();
    SchoolUserMembership::query()->create([
        'school_id' => $this->school->id,
        'user_id' => $user->id,
        'is_active' => true,
        'joined_at' => now()->toDateString(),
    ]);

    $coachRole = Role::create([
        'name' => 'coach',
        'guard_name' => 'web',
        'school_id' => null,
    ]);
    $customRole = Role::create([
        'name' => 'operator_absensi',
        'guard_name' => 'web',
        'school_id' => $this->school->id,
    ]);
    $otherSchool = School::factory()->create();
    SchoolUserMembership::query()->create([
        'school_id' => $otherSchool->id,
        'user_id' => $user->id,
        'is_active' => true,
        'joined_at' => now()->toDateString(),
    ]);
    $otherSchoolRole = Role::create([
        'name' => 'operator_lain',
        'guard_name' => 'web',
        'school_id' => $otherSchool->id,
    ]);
    setPermissionsTeamId($otherSchool->id);
    $user->assignRole($otherSchoolRole);
    setPermissionsTeamId($this->school->id);

    Livewire::test(RoleManagementIndex::class)
        ->call('selectUser', $user->id)
        ->set('selectedRoleIds', [$coachRole->id, $customRole->id])
        ->call('assignRolesToUser')
        ->assertHasNoErrors()
        ->assertSee('Role akun berhasil diperbarui');

    setPermissionsTeamId($this->school->id);
    $user->unsetRelation('roles');

    expect($user->roles()->pluck('roles.id')->sort()->values()->all())
        ->toBe([$coachRole->id, $customRole->id]);

    setPermissionsTeamId($otherSchool->id);
    $user->unsetRelation('roles');

    expect($user->roles()->pluck('roles.id')->all())
        ->toBe([$otherSchoolRole->id]);
});

it('does not allow delegating system administrator roles or cross school roles', function (): void {
    $user = User::factory()->create();
    SchoolUserMembership::query()->create([
        'school_id' => $this->school->id,
        'user_id' => $user->id,
        'is_active' => true,
        'joined_at' => now()->toDateString(),
    ]);

    $otherSchool = School::factory()->create();
    $otherSchoolRole = Role::create([
        'name' => 'operator_lain',
        'guard_name' => 'web',
        'school_id' => $otherSchool->id,
    ]);

    $systemAdminRole = Role::create([
        'name' => 'school_admin',
        'guard_name' => 'web',
        'school_id' => null,
    ]);

    Livewire::test(RoleManagementIndex::class)
        ->call('selectUser', $user->id)
        ->set('selectedRoleIds', [$otherSchoolRole->id])
        ->call('assignRolesToUser')
        ->assertHasErrors(['selectedRoleIds.0']);

    Livewire::test(RoleManagementIndex::class)
        ->call('selectUser', $user->id)
        ->set('selectedRoleIds', [$systemAdminRole->id])
        ->call('assignRolesToUser')
        ->assertHasErrors(['selectedRoleIds.0']);
});

it('does not allow choosing users outside the active school', function (): void {
    $otherSchool = School::factory()->create();
    $user = User::factory()->create();
    SchoolUserMembership::query()->create([
        'school_id' => $otherSchool->id,
        'user_id' => $user->id,
        'is_active' => true,
        'joined_at' => now()->toDateString(),
    ]);

    expect(fn () => Livewire::test(RoleManagementIndex::class)
        ->call('selectUser', $user->id))
        ->toThrow(ModelNotFoundException::class);
});
