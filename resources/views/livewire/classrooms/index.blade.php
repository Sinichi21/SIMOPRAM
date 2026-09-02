<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-semibold">
            Data Kelas
        </h1>

        <p class="text-sm text-zinc-500">
            Kelola kelas pada sekolah aktif.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @can('classrooms.manage')

        <form
            wire:submit="save"
            class="rounded-xl border bg-white p-6 shadow-sm dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">
                {{ $editingId ? 'Edit Kelas' : 'Tambah Kelas' }}
            </h2>

            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Nama Kelas
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="Contoh: VI A"
                        class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"
                    >

                    @error('name')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Tingkat
                    </label>

                    <input
                        type="number"
                        wire:model="grade"
                        min="1"
                        max="12"
                        placeholder="Contoh: 6"
                        class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"
                    >

                    @error('grade')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

            </div>

            <div class="mt-4">

                <label class="mb-1 block text-sm font-medium">
                    Keterangan
                </label>

                <textarea
                    wire:model="description"
                    rows="3"
                    class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"
                ></textarea>

            </div>

            <label class="mt-4 flex items-center gap-2">
                <input
                    type="checkbox"
                    wire:model="is_active"
                >

                Kelas aktif
            </label>

            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-white dark:bg-white dark:text-zinc-900"
                >
                    {{ $editingId ? 'Simpan Perubahan' : 'Tambah' }}
                </button>

                @if ($editingId)
                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg border px-4 py-2"
                    >
                        Batal
                    </button>
                @endif

            </div>

        </form>

    @endcan

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-zinc-900">

        <div class="mb-4 flex items-center justify-between">

            <h2 class="text-lg font-semibold">
                Daftar Kelas
            </h2>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari kelas..."
                class="rounded-lg border px-3 py-2"
            >

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead>
                    <tr class="border-b text-sm text-zinc-500">
                        <th class="p-3">Kelas</th>
                        <th class="p-3">Tingkat</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($classrooms as $classroom)

                        <tr
                            class="border-b"
                            wire:key="classroom-{{ $classroom->id }}"
                        >

                            <td class="p-3 font-medium">
                                {{ $classroom->name }}
                            </td>

                            <td class="p-3">
                                {{ $classroom->grade ?? '-' }}
                            </td>

                            <td class="p-3">
                                {{ $classroom->is_active ? 'Aktif' : 'Nonaktif' }}
                            </td>

                            <td class="p-3">

                                @can('classrooms.manage')

                                    <button
                                        wire:click="edit({{ $classroom->id }})"
                                        class="mr-2 rounded border px-3 py-1"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        wire:click="toggleStatus({{ $classroom->id }})"
                                        class="rounded border px-3 py-1"
                                    >
                                        {{ $classroom->is_active
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan'
                                        }}
                                    </button>

                                @endcan

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="4"
                                class="p-6 text-center text-zinc-500"
                            >
                                Belum ada kelas.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-4">
            {{ $classrooms->links() }}
        </div>

    </div>

</div>