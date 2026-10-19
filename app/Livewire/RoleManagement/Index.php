<?php

namespace App\Livewire\RoleManagement;

use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Index extends Component
{
    #[Locked]
    public ?int $roleId = null;

    public string $name = '';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public bool $showForm = false;

    public string $search = '';

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
                    ->where(fn (Builder $query): Builder => $query
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

        $permissionGroups = Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $this->protectedPermissions())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->groupBy(fn (Permission $permission): string => str((string) $permission->name)->before('.')->replace('_', ' ')->title()->toString());

        return view('livewire.role-management.index', compact('roles', 'permissionGroups'));
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
