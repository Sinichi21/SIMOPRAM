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
