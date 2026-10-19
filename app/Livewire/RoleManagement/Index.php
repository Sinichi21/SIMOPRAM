<?php

namespace App\Livewire\RoleManagement;

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
    private const ASSIGNABLE_SYSTEM_ROLES = ['coach', 'student', 'principal'];

    #[Locked]
    public ?int $roleId = null;

    public string $name = '';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public bool $showForm = false;

    public string $search = '';

    public string $userSearch = '';

    #[Locked]
    public ?int $selectedUserId = null;

    /** @var array<int, int|string> */
    public array $selectedRoleIds = [];

    public function mount(): void
    {
        $this->authorizeManagement();
    }

    public function startCreate(): void
    {
        $this->authorizeManagement();
        $this->resetForm();
        $this->showForm = true;
    }

    public function editRole(int $roleId): void
    {
        $this->authorizeManagement();

        $role = $this->findSchoolRole($roleId);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions()
            ->pluck('name')
            ->reject(fn (string $permission): bool => in_array($permission, $this->protectedPermissions(), true))
            ->values()
            ->all();
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->authorizeManagement();
        $this->resetForm();
    }

    public function selectUser(int $userId): void
    {
        $this->authorizeManagement();

        $user = $this->findActiveSchoolUser($userId);
        $schoolId = app(SchoolContext::class)->id();
        $previousTeamId = getPermissionsTeamId();

        try {
            setPermissionsTeamId($schoolId);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->selectedUserId = $user->id;
            $this->selectedRoleIds = $user->roles()
                ->pluck('roles.id')
                ->map(fn (int $roleId): int => $roleId)
                ->all();
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    public function assignRolesToUser(): void
    {
        $this->authorizeManagement();

        $schoolId = app(SchoolContext::class)->id();
        $user = $this->findActiveSchoolUser((int) $this->selectedUserId);
        $assignableRoles = $this->assignableRoles($schoolId);

        $validated = $this->validate([
            'selectedRoleIds' => ['array'],
            'selectedRoleIds.*' => ['integer', Rule::in($assignableRoles->modelKeys())],
        ], [
            'selectedRoleIds.*.in' => 'Role yang dipilih tidak tersedia untuk sekolah aktif.',
        ]);

        $roles = $assignableRoles
            ->whereIn('id', $validated['selectedRoleIds'] ?? [])
            ->all();

        $previousTeamId = getPermissionsTeamId();

        try {
            setPermissionsTeamId($schoolId);
            DB::transaction(function () use ($user, $roles): void {
                $user->unsetRelation('roles')->unsetRelation('permissions')->syncRoles($roles);
            });
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->selectedRoleIds = collect($roles)->map(fn (Role $role): int => $role->id)->all();

        session()->flash('status', 'Role akun berhasil diperbarui untuk sekolah aktif.');
    }

    public function saveRole(): void
    {
        $this->authorizeManagement();

        $schoolId = app(SchoolContext::class)->id();
        $role = $this->roleId ? $this->findSchoolRole($this->roleId) : null;

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'alpha_dash:ascii',
                Rule::notIn($this->protectedRoleNames()),
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query
                        ->where('guard_name', 'web')
                        ->where('school_id', $schoolId))
                    ->ignore($role?->id),
            ],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
                Rule::notIn($this->protectedPermissions()),
            ],
        ], [
            'name.alpha_dash' => 'Nama role hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'name.not_in' => 'Nama tersebut dicadangkan untuk role sistem.',
            'name.unique' => 'Nama role sudah digunakan di sekolah aktif.',
            'selectedPermissions.*.not_in' => 'Permission pengelolaan role tidak dapat diberikan ke role khusus sekolah.',
        ]);

        $permissions = array_values(array_unique($validated['selectedPermissions'] ?? []));

        DB::transaction(function () use ($role, $validated, $permissions, $schoolId): void {
            $role ??= Role::query()->create([
                'name' => $validated['name'],
                'guard_name' => 'web',
                'school_id' => $schoolId,
            ]);

            if ($role->name !== $validated['name']) {
                $role->name = $validated['name'];
                $role->save();
            }

            $role->syncPermissions($permissions);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        session()->flash('status', $this->roleId
            ? 'Role dan permission berhasil diperbarui.'
            : 'Role baru berhasil dibuat.');

        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
    }

    public function render(): View
    {
        $this->authorizeManagement();
        $schoolId = app(SchoolContext::class)->id();

        $roles = Role::query()
            ->where('school_id', $schoolId)
            ->withCount('users')
            ->with('permissions:id,name')
            ->when($this->search !== '', fn (Builder $query): Builder => $query
                ->where('name', 'like', '%'.trim($this->search).'%'))
            ->orderBy('name')
            ->get();

        $systemRoles = Role::query()
            ->whereNull('school_id')
            ->withCount('users')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get();

        $availableRoles = $this->assignableRoles($schoolId);
        $roleLabels = [
            'coach' => 'Pembina',
            'student' => 'Siswa',
            'principal' => 'Kepala Sekolah',
            'school_admin' => 'Admin Sekolah',
            'scout_admin' => 'Admin Pramuka',
            'super_admin' => 'Super Admin',
        ];
        $matchingUsers = $this->matchingUsers($schoolId);
        $selectedUser = $this->selectedUserId
            ? $this->findActiveSchoolUserOrNull($this->selectedUserId)
            : null;

        $permissionGroups = Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $this->protectedPermissions())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->groupBy(fn (Permission $permission): string => str((string) $permission->name)->before('.')->replace('_', ' ')->title()->toString());

        return view('livewire.role-management.index', compact(
            'roles',
            'systemRoles',
            'availableRoles',
            'roleLabels',
            'matchingUsers',
            'selectedUser',
            'permissionGroups',
        ));
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()?->can('roles.manage'), 403);
        abort_unless(app(SchoolContext::class)->hasSchool(), 409, 'Pilih sekolah aktif terlebih dahulu.');
    }

    private function findSchoolRole(int $roleId): Role
    {
        return Role::query()
            ->where('school_id', app(SchoolContext::class)->id())
            ->findOrFail($roleId);
    }

    private function findActiveSchoolUser(int $userId): User
    {
        return User::query()
            ->whereKey($userId)
            ->where('is_active', true)
            ->whereHas('schoolMemberships', fn (Builder $query): Builder => $query
                ->where('school_id', app(SchoolContext::class)->id())
                ->where('is_active', true)
                ->whereNull('left_at'))
            ->firstOrFail();
    }

    private function findActiveSchoolUserOrNull(int $userId): ?User
    {
        return User::query()
            ->whereKey($userId)
            ->where('is_active', true)
            ->whereHas('schoolMemberships', fn (Builder $query): Builder => $query
                ->where('school_id', app(SchoolContext::class)->id())
                ->where('is_active', true)
                ->whereNull('left_at'))
            ->first();
    }

    /** @return Collection<int, Role> */
    private function assignableRoles(int $schoolId): Collection
    {
        return Role::query()
            ->where(function (Builder $query) use ($schoolId): void {
                $query->where('school_id', $schoolId)
                    ->orWhere(function (Builder $query): void {
                        $query->whereNull('school_id')
                            ->whereIn('name', self::ASSIGNABLE_SYSTEM_ROLES);
                    });
            })
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();
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
    private function protectedPermissions(): array
    {
        return ['roles.manage', 'roles.view'];
    }

    /** @return array<int, string> */
    private function protectedRoleNames(): array
    {
        return ['scout_admin', 'school_admin', 'principal', 'coach', 'student', 'super_admin'];
    }

    private function resetForm(): void
    {
        $this->reset(['roleId', 'name', 'selectedPermissions', 'showForm']);
        $this->resetValidation();
    }
}
