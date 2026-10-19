<?php

namespace App\Livewire\SystemRoleManagement;

use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Index extends Component
{
    private const EDITABLE_SYSTEM_ROLES = ['scout_admin', 'school_admin', 'principal', 'coach', 'student'];

    private const DELEGATABLE_SYSTEM_ROLES = ['school_admin', 'scout_admin'];

    #[Locked]
    public ?int $editingRoleId = null;

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public ?int $selectedSchoolId = null;

    public string $userSearch = '';

    #[Locked]
    public ?int $selectedUserId = null;

    public string $roleToDelegate = 'school_admin';

    public function mount(): void
    {
        $this->authorizeSuperAdmin();
        $this->selectedSchoolId = app(SchoolContext::class)->id();
    }

    public function editSystemRole(int $roleId): void
    {
        $this->authorizeSuperAdmin();

        $role = $this->findEditableSystemRole($roleId);
        $this->editingRoleId = $role->id;
        $this->selectedPermissions = $role->permissions()
            ->orderBy('permissions.name')
            ->pluck('permissions.name')
            ->all();
    }

    public function cancelSystemRoleEdit(): void
    {
        $this->authorizeSuperAdmin();
        $this->resetRoleForm();
    }

    public function saveSystemRolePermissions(): void
    {
        $this->authorizeSuperAdmin();

        $role = $this->findEditableSystemRole((int) $this->editingRoleId);
        $validated = $this->validate([
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ]);

        $previousTeamId = getPermissionsTeamId();

        try {
            setPermissionsTeamId(null);
            DB::transaction(function () use ($role, $validated): void {
                $editableRole = Role::query()
                    ->whereNull('school_id')
                    ->whereIn('name', self::EDITABLE_SYSTEM_ROLES)
                    ->lockForUpdate()
                    ->findOrFail($role->id);

                $editableRole->syncPermissions(array_values(array_unique($validated['selectedPermissions'] ?? [])));
            });
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->resetRoleForm();
        session()->flash('status', 'Permission role bawaan berhasil diperbarui.');
    }

    public function updatedSelectedSchoolId(): void
    {
        $this->authorizeSuperAdmin();
        $this->selectedUserId = null;
        $this->userSearch = '';
    }

    public function selectUser(int $userId): void
    {
        $this->authorizeSuperAdmin();
        $school = $this->selectedSchool();
        $this->selectedUserId = $this->findActiveSchoolUser($userId, $school->id)->id;
    }

    public function delegateRole(): void
    {
        $this->authorizeSuperAdmin();
        $school = $this->selectedSchool();
        $validated = $this->validate([
            'roleToDelegate' => ['required', Rule::in(self::DELEGATABLE_SYSTEM_ROLES)],
        ]);
        $user = $this->findActiveSchoolUser((int) $this->selectedUserId, $school->id);
        $previousTeamId = getPermissionsTeamId();

        try {
            setPermissionsTeamId($school->id);
            $role = Role::query()
                ->whereNull('school_id')
                ->where('guard_name', 'web')
                ->where('name', $validated['roleToDelegate'])
                ->firstOrFail();

            DB::transaction(function () use ($user, $role): void {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $this->findActiveSchoolUser($lockedUser->id, (int) $this->selectedSchoolId);
                $lockedUser->unsetRelation('roles')->unsetRelation('permissions')->assignRole($role);
            });
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        session()->flash('status', 'Role berhasil didelegasikan untuk sekolah yang dipilih.');
    }

    public function revokeRole(string $roleName): void
    {
        $this->authorizeSuperAdmin();
        abort_unless(in_array($roleName, self::DELEGATABLE_SYSTEM_ROLES, true), 422);

        $school = $this->selectedSchool();
        $user = $this->findActiveSchoolUser((int) $this->selectedUserId, $school->id);
        $previousTeamId = getPermissionsTeamId();

        try {
            setPermissionsTeamId($school->id);
            $role = Role::query()
                ->whereNull('school_id')
                ->where('guard_name', 'web')
                ->where('name', $roleName)
                ->firstOrFail();

            DB::transaction(function () use ($user, $role, $school): void {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $this->findActiveSchoolUser($lockedUser->id, $school->id);
                $lockedUser->unsetRelation('roles')->unsetRelation('permissions')->removeRole($role);
            });
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        session()->flash('status', 'Delegasi role berhasil dicabut dari sekolah yang dipilih.');
    }

    public function render(): View
    {
        $this->authorizeSuperAdmin();

        $systemRoles = Role::query()
            ->whereNull('school_id')
            ->where('guard_name', 'web')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get();
        $hasSuperAdminRole = $systemRoles->contains('name', 'super_admin');

        $permissionGroups = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->groupBy(fn (Permission $permission): string => str((string) $permission->name)->before('.')->replace('_', ' ')->title()->toString());

        $schools = School::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $matchingUsers = $this->selectedSchoolId
            ? $this->matchingUsers($this->selectedSchoolId)
            : collect();

        $selectedUser = $this->selectedSchoolId && $this->selectedUserId
            ? $this->findActiveSchoolUserOrNull($this->selectedUserId, $this->selectedSchoolId)
            : null;

        $delegatedRoleNames = $this->delegatedRoleNames($selectedUser);
        $roleLabels = [
            'scout_admin' => 'Admin Pramuka',
            'school_admin' => 'Admin Sekolah',
            'principal' => 'Kepala Sekolah',
            'coach' => 'Pembina',
            'student' => 'Siswa',
            'super_admin' => 'Super Admin',
        ];

        return view('livewire.system-role-management.index', compact(
            'systemRoles',
            'hasSuperAdminRole',
            'permissionGroups',
            'schools',
            'matchingUsers',
            'selectedUser',
            'delegatedRoleNames',
            'roleLabels',
        ));
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    private function findEditableSystemRole(int $roleId): Role
    {
        return Role::query()
            ->whereNull('school_id')
            ->where('guard_name', 'web')
            ->whereIn('name', self::EDITABLE_SYSTEM_ROLES)
            ->findOrFail($roleId);
    }

    private function selectedSchool(): School
    {
        return School::query()
            ->where('is_active', true)
            ->findOrFail($this->selectedSchoolId);
    }

    private function findActiveSchoolUser(int $userId, int $schoolId): User
    {
        return User::query()
            ->whereKey($userId)
            ->where('is_active', true)
            ->whereHas('schoolMemberships', fn (Builder $query): Builder => $query
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->whereNull('left_at'))
            ->firstOrFail();
    }

    private function findActiveSchoolUserOrNull(int $userId, int $schoolId): ?User
    {
        return User::query()
            ->whereKey($userId)
            ->where('is_active', true)
            ->whereHas('schoolMemberships', fn (Builder $query): Builder => $query
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->whereNull('left_at'))
            ->first();
    }

    /** @return Collection<int, User> */
    private function matchingUsers(int $schoolId): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('schoolMemberships', fn (Builder $query): Builder => $query
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->whereNull('left_at'))
            ->when(trim($this->userSearch) !== '', function (Builder $query): void {
                $search = '%'.trim($this->userSearch).'%';
                $query->where(fn (Builder $query): Builder => $query
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search));
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);
    }

    /** @return array<int, string> */
    private function delegatedRoleNames(?User $user): array
    {
        if (! $user || ! $this->selectedSchoolId) {
            return [];
        }

        $previousTeamId = getPermissionsTeamId();

        try {
            setPermissionsTeamId($this->selectedSchoolId);
            $user->unsetRelation('roles')->unsetRelation('permissions');

            return $user->roles()
                ->whereNull('roles.school_id')
                ->whereIn('roles.name', self::DELEGATABLE_SYSTEM_ROLES)
                ->orderBy('roles.name')
                ->pluck('roles.name')
                ->all();
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    private function resetRoleForm(): void
    {
        $this->reset(['editingRoleId', 'selectedPermissions']);
        $this->resetValidation();
    }
}
