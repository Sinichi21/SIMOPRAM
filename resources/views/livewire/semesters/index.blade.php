<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-semibold">
            Semester
        </h1>

        <p class="text-sm text-zinc-500">
            Kelola semester pada tahun ajaran sekolah aktif.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @can('semesters.manage')

        <form
            wire:submit="save"
            class="rounded-xl border bg-white p-6 shadow-sm dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">
                {{ $editingId ? 'Edit Semester' : 'Tambah Semester' }}
            </h2>

            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Tahun Ajaran
                    </label>

                    <select
                        wire:model="academic_year_id"
                        class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"
                    >
                        <option value="">
                            Pilih tahun ajaran
                        </option>

                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}">
                                {{ $year->name }}
                                {{ $year->is_active ? '— Aktif' : '' }}
                            </option>
                        @endforeach
                    </select>

                    @error('academic_year_id')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Semester
                    </label>

                    <select
                        wire:model="semester_number"
                        class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"
                    >
                        <option value="">
                            Pilih semester
                        </option>

                        <option value="1">
                            Ganjil
                        </option>

                        <option value="2">
                            Genap
                        </option>
                    </select>

                    @error('semester_number')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Tanggal Mulai
                    </label>

                    <input
                        type="date"
                        wire:model="start_date"
                        class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"
                    >

                    @error('start_date')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Tanggal Selesai
                    </label>

                    <input
                        type="date"
                        wire:model="end_date"
                        class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"
                    >

                    @error('end_date')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

            </div>

            <label class="mt-4 flex items-center gap-2">
                <input
                    type="checkbox"
                    wire:model="is_active"
                >

                Jadikan semester aktif
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
                Daftar Semester
            </h2>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari tahun ajaran..."
                class="rounded-lg border px-3 py-2"
            >

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead>
                    <tr class="border-b text-sm text-zinc-500">
                        <th class="p-3">Tahun</th>
                        <th class="p-3">Semester</th>
                        <th class="p-3">Mulai</th>
                        <th class="p-3">Selesai</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($semesters as $semester)

                        <tr
                            class="border-b"
                            wire:key="semester-{{ $semester->id }}"
                        >

                            <td class="p-3">
                                {{ $semester->academicYear->name }}
                            </td>

                            <td class="p-3">
                                {{ $semester->name }}
                            </td>

                            <td class="p-3">
                                {{ $semester->start_date->format('d-m-Y') }}
                            </td>

                            <td class="p-3">
                                {{ $semester->end_date->format('d-m-Y') }}
                            </td>

                            <td class="p-3">
                                {{ $semester->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </td>

                            <td class="p-3">

                                @can('semesters.manage')

                                    <button
                                        wire:click="edit({{ $semester->id }})"
                                        class="mr-2 rounded border px-3 py-1"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        wire:click="delete({{ $semester->id }})"
                                        wire:confirm="Hapus semester ini?"
                                        class="rounded border px-3 py-1 text-red-600"
                                    >
                                        Hapus
                                    </button>

                                @endcan

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="6"
                                class="p-6 text-center text-zinc-500"
                            >
                                Belum ada data semester.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-4">
            {{ $semesters->links() }}
        </div>

    </div>

</div>