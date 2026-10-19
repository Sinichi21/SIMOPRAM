<div class="space-y-6">
    <div>
        <flux:heading size="xl">Role & Permission Bawaan</flux:heading>
        <flux:text class="mt-1 max-w-4xl">Atur permission role bawaan SIMPRAM dan delegasikan Admin Sekolah atau Admin Pramuka ke akun pada sekolah tertentu.</flux:text>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300">
            {{ session('status') }}
        </div>
    @endif

    <flux:callout icon="information-circle" variant="secondary">
        Permission pada role bawaan berlaku di semua sekolah. Penetapan Admin Sekolah dan Admin Pramuka hanya berlaku untuk sekolah yang dipilih. Role Super Admin tidak dapat diubah atau didelegasikan.
    </flux:callout>

    <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
        <div>
            <flux:heading size="lg">Role bawaan sistem</flux:heading>
            <flux:text class="mt-1">Pilih role untuk mengatur permission. Nama dan cakupan role bawaan tidak dapat diubah.</flux:text>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @unless ($hasSuperAdminRole)
                <article class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-medium">Super Admin</div>
                        <flux:badge color="zinc">Tidak dapat diubah</flux:badge>
                    </div>
                    <div class="mt-2 text-sm text-zinc-500">Akses penuh dikelola langsung oleh sistem.</div>
                </article>
            @endunless

            @foreach ($systemRoles as $systemRole)
                <article wire:key="system-role-{{ $systemRole->id }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-medium">{{ $roleLabels[$systemRole->name] ?? str($systemRole->name)->replace('_', ' ')->title() }}</div>
                        @if ($systemRole->name === 'super_admin')
                            <flux:badge color="zinc">Tidak dapat diubah</flux:badge>
                        @else
                            <flux:badge color="zinc">Bawaan</flux:badge>
                        @endif
                    </div>
                    <div class="mt-2 text-sm text-zinc-500">{{ $systemRole->permissions->count() }} permission</div>
                    @if ($systemRole->name !== 'super_admin')
                        <flux:button class="mt-3" size="sm" variant="outline" icon="pencil-square" wire:click="editSystemRole({{ $systemRole->id }})">
                            Atur Permission
                        </flux:button>
                    @endif
                </article>
            @endforeach
        </div>

        @if ($editingRoleId)
            <form wire:submit="saveSystemRolePermissions" class="space-y-5 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                <div>
                    <flux:heading size="lg">Atur permission: {{ $roleLabels[$systemRoles->firstWhere('id', $editingRoleId)?->name] ?? str((string) $systemRoles->firstWhere('id', $editingRoleId)?->name)->replace('_', ' ')->title() }}</flux:heading>
                    <flux:text class="mt-1">Perubahan langsung berlaku untuk pemegang role ini di semua sekolah.</flux:text>
                </div>

                @error('selectedPermissions') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach ($permissionGroups as $groupName => $permissions)
                        <section wire:key="system-permission-group-{{ \Illuminate\Support\Str::slug($groupName) }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <h3 class="mb-3 text-sm font-semibold capitalize">{{ $groupName }}</h3>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($permissions as $permission)
                                    <label wire:key="system-permission-{{ $permission->id }}" class="flex items-start gap-2 text-sm">
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

                <div class="flex flex-wrap gap-2">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveSystemRolePermissions">
                        Simpan Permission
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="cancelSystemRoleEdit">Batal</flux:button>
                </div>
            </form>
        @endif
    </section>

    <section class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
        <div>
            <flux:heading size="lg">Delegasi Admin Sekolah / Admin Pramuka</flux:heading>
            <flux:text class="mt-1">Pilih sekolah dan akun anggota aktif. Role yang didelegasikan hanya memberikan akses pada sekolah tersebut.</flux:text>
        </div>

        <div class="max-w-xl">
            <flux:field>
                <flux:label>Sekolah tujuan</flux:label>
                <flux:select wire:model.live="selectedSchoolId" placeholder="Pilih sekolah">
                    @foreach ($schools as $school)
                        <flux:select.option wire:key="delegation-school-{{ $school->id }}" value="{{ $school->id }}">{{ $school->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedSchoolId" />
            </flux:field>
        </div>

        @if ($selectedSchoolId)
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
                <div class="space-y-3">
                    <flux:input wire:model.live.debounce.300ms="userSearch" type="search" label="Cari akun anggota sekolah" placeholder="Nama atau email..." />
                    <div class="max-h-72 space-y-2 overflow-y-auto">
                        @forelse ($matchingUsers as $user)
                            <button
                                type="button"
                                wire:key="delegation-user-{{ $user->id }}"
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
                        <div class="space-y-5">
                            <div>
                                <flux:heading>{{ $selectedUser->name }}</flux:heading>
                                <flux:text>{{ $selectedUser->email }}</flux:text>
                            </div>

                            <form wire:submit="delegateRole" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                <flux:field class="flex-1">
                                    <flux:label>Role yang didelegasikan</flux:label>
                                    <flux:select wire:model="roleToDelegate">
                                        <flux:select.option value="school_admin">Admin Sekolah</flux:select.option>
                                        <flux:select.option value="scout_admin">Admin Pramuka</flux:select.option>
                                    </flux:select>
                                    <flux:error name="roleToDelegate" />
                                </flux:field>
                                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="delegateRole">
                                    Delegasikan Role
                                </flux:button>
                            </form>

                            <div class="space-y-2">
                                <flux:heading size="sm">Role admin pada sekolah ini</flux:heading>
                                @forelse ($delegatedRoleNames as $delegatedRole)
                                    <div wire:key="delegated-role-{{ $delegatedRole }}" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                        <span class="text-sm font-medium">{{ $roleLabels[$delegatedRole] }}</span>
                                        <flux:button size="sm" variant="danger" wire:click="revokeRole('{{ $delegatedRole }}')" wire:confirm="Cabut role {{ $roleLabels[$delegatedRole] }} dari akun ini di sekolah terpilih?">
                                            Cabut
                                        </flux:button>
                                    </div>
                                @empty
                                    <p class="text-sm text-zinc-500">Belum ada role admin yang didelegasikan untuk akun ini di sekolah terpilih.</p>
                                @endforelse
                            </div>
                        </div>
                    @else
                        <div class="flex h-full min-h-40 items-center justify-center rounded-lg border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700">
                            Pilih akun di sebelah kiri untuk mengelola delegasi role.
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </section>
</div>
