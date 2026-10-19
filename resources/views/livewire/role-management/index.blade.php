<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl">Role & Permission</flux:heading>
            <flux:text class="mt-1 max-w-3xl">Kelola role khusus sekolah aktif dan tentukan permission yang dimiliki setiap role.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="startCreate">Add New Role</flux:button>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
        <div>
            <flux:heading size="lg">Role bawaan sistem</flux:heading>
            <flux:text class="mt-1">Role ini dikelola sistem dan hanya dapat dilihat. Administrator sekolah tidak dapat mengubah permission bawaan.</flux:text>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($systemRoles as $systemRole)
                <article wire:key="system-role-{{ $systemRole->id }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-medium">{{ $roleLabels[$systemRole->name] ?? str($systemRole->name)->replace('_', ' ')->title() }}</div>
                        <flux:badge color="zinc">Bawaan</flux:badge>
                    </div>
                    <div class="mt-2 text-sm text-zinc-500">{{ $systemRole->permissions->count() }} permission · {{ $systemRole->users_count }} pengguna</div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
        <div>
            <flux:heading size="lg">Delegasi role ke akun</flux:heading>
            <flux:text class="mt-1">Pilih akun aktif yang menjadi anggota sekolah ini, lalu tetapkan role untuk sekolah aktif. Role administrator sistem tidak dapat didelegasikan.</flux:text>
        </div>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
            <div class="space-y-3">
                <flux:input wire:model.live.debounce.300ms="userSearch" type="search" label="Cari akun sekolah" placeholder="Nama atau email..." />
                <div class="max-h-72 space-y-2 overflow-y-auto">
                    @forelse ($matchingUsers as $user)
                        <button
                            type="button"
                            wire:key="role-user-{{ $user->id }}"
                            wire:click="selectUser({{ $user->id }})"
                            @class([
                                'w-full rounded-lg border px-3 py-2 text-left transition',
                                'border-zinc-900 bg-zinc-100 dark:border-zinc-100 dark:bg-zinc-800' => $selectedUser?->id === $user->id,
                                'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/60' => $selectedUser?->id !== $user->id,
                            ])
                        >
                            <span class="block text-sm font-medium">{{ $user->name }}</span>
                            <span class="block text-xs text-zinc-500">{{ $user->email }}</span>
                        </button>
                    @empty
                        <p class="rounded-lg border border-dashed border-zinc-300 px-3 py-4 text-sm text-zinc-500 dark:border-zinc-700">Tidak ada akun aktif yang cocok di sekolah ini.</p>
                    @endforelse
                </div>
            </div>

            <div>
                @if ($selectedUser)
                    <form wire:submit="assignRolesToUser" class="space-y-4">
                        <div>
                            <flux:heading>{{ $selectedUser->name }}</flux:heading>
                            <flux:text>{{ $selectedUser->email }}</flux:text>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($availableRoles as $availableRole)
                                <label wire:key="assign-role-{{ $availableRole->id }}" class="flex items-start gap-2 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                                    <input type="checkbox" value="{{ $availableRole->id }}" wire:model="selectedRoleIds" class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600">
                                    <span>
                                        <span class="block font-medium">{{ $roleLabels[$availableRole->name] ?? str($availableRole->name)->replace('_', ' ')->title() }}</span>
                                        <span class="text-xs text-zinc-500">{{ $availableRole->school_id === null ? 'Role bawaan' : 'Role sekolah' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedRoleIds.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="assignRolesToUser">
                            Simpan Delegasi Role
                        </flux:button>
                    </form>
                @else
                    <div class="flex h-full min-h-40 items-center justify-center rounded-lg border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700">
                        Pilih akun di sebelah kiri untuk mengatur role.
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($showForm)
        <section class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ $roleId ? 'Edit Role' : 'Add New Role' }}</flux:heading>
                    <flux:text class="mt-1">Pilih permission sesuai tugas role. Perubahan permission berlaku untuk role ini di sekolah aktif.</flux:text>
                </div>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="cancelForm">Tutup</flux:button>
            </div>

            <form wire:submit="saveRole" class="space-y-5">
                <div class="max-w-xl">
                    <flux:input
                        wire:model="name"
                        label="Nama role"
                        placeholder="contoh: operator_absensi"
                        description="Gunakan huruf kecil, angka, garis bawah, atau tanda hubung."
                        required
                    />
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-3">
                    <div>
                        <flux:heading>Permission setting</flux:heading>
                        <flux:text class="mt-1">Centang hak akses yang diperlukan. Permission pengelolaan role tidak dapat diberikan dari form ini.</flux:text>
                    </div>

                    @error('selectedPermissions') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    <div class="grid gap-4 xl:grid-cols-2">
                        @foreach ($permissionGroups as $groupName => $permissions)
                            <section wire:key="permission-group-{{ \Illuminate\Support\Str::slug($groupName) }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <h3 class="mb-3 text-sm font-semibold capitalize">{{ $groupName }}</h3>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($permissions as $permission)
                                        <label wire:key="permission-{{ $permission->id }}" class="flex items-start gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                value="{{ $permission->name }}"
                                                wire:model="selectedPermissions"
                                                class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600"
                                            >
                                            <span class="break-all">{{ $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveRole">
                        <span wire:loading.remove wire:target="saveRole">{{ $roleId ? 'Simpan Perubahan' : 'Buat Role' }}</span>
                        <span wire:loading wire:target="saveRole">Menyimpan...</span>
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="cancelForm">Batal</flux:button>
                </div>
            </form>
        </section>
    @endif

    <div class="max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" type="search" label="Cari role" placeholder="Cari nama role..." />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Permission</flux:table.column>
                <flux:table.column>Pengguna</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($roles as $role)
                    <flux:table.row wire:key="role-{{ $role->id }}">
                        <flux:table.cell>
                            <div class="font-medium">{{ $role->name }}</div>
                            <div class="mt-1 text-xs text-zinc-500">Role khusus sekolah</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $role->permissions->count() }} permission</flux:table.cell>
                        <flux:table.cell>{{ $role->users_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="outline" icon="pencil-square" wire:click="editRole({{ $role->id }})">Edit / Atur Permission</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">
                            <div class="py-6 text-center">
                                <p class="font-medium">Belum ada role khusus sekolah.</p>
                                <p class="mt-1 text-sm text-zinc-500">Klik Add New Role untuk membuat role pertama.</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
