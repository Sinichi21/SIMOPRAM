<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-semibold">
            Tahun Ajaran
        </h1>

        <p class="text-sm text-zinc-500">
            Kelola tahun ajaran sekolah aktif.
        </p>
    </div>

    @if (session()->has('success'))
        <div
            class="rounded-lg border border-green-200
                   bg-green-50 p-4 text-green-700"
        >
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div
            class="rounded-lg border border-red-200
                   bg-red-50 p-4 text-red-700"
        >
            {{ session('error') }}
        </div>
    @endif

    @can('academic_years.manage')

        <form
            wire:submit="save"
            class="rounded-xl border
                   bg-white p-6 shadow-sm
                   dark:bg-zinc-900"
        >

            <div class="mb-5">
                <h2 class="text-lg font-semibold">
                    {{ $editingId
                        ? 'Edit Tahun Ajaran'
                        : 'Tambah Tahun Ajaran'
                    }}
                </h2>
            </div>

            <div
                class="grid gap-4
                       md:grid-cols-3"
            >

                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tahun Ajaran
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="2026/2027"
                        class="w-full rounded-lg border
                               px-3 py-2
                               dark:bg-zinc-800"
                    >

                    @error('name')
                        <span
                            class="text-sm text-red-500"
                        >
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tanggal Mulai
                    </label>

                    <input
                        type="date"
                        wire:model="start_date"
                        class="w-full rounded-lg border
                               px-3 py-2
                               dark:bg-zinc-800"
                    >

                    @error('start_date')
                        <span
                            class="text-sm text-red-500"
                        >
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tanggal Selesai
                    </label>

                    <input
                        type="date"
                        wire:model="end_date"
                        class="w-full rounded-lg border
                               px-3 py-2
                               dark:bg-zinc-800"
                    >

                    @error('end_date')
                        <span
                            class="text-sm text-red-500"
                        >
                            {{ $message }}
                        </span>
                    @enderror
                </div>

            </div>

            <div class="mt-4">
                <label
                    class="flex items-center gap-2"
                >

                    <input
                        type="checkbox"
                        wire:model="is_active"
                    >

                    <span>
                        Jadikan tahun ajaran aktif
                    </span>

                </label>
            </div>

            <div
                class="mt-6 flex gap-2"
            >

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900
                           px-4 py-2 text-white
                           dark:bg-white
                           dark:text-zinc-900"
                >
                    {{ $editingId
                        ? 'Simpan Perubahan'
                        : 'Tambah'
                    }}
                </button>

                @if ($editingId)

                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg border
                               px-4 py-2"
                    >
                        Batal
                    </button>

                @endif

            </div>

        </form>

    @endcan

    <div
        class="rounded-xl border
               bg-white p-6 shadow-sm
               dark:bg-zinc-900"
    >

        <div
            class="mb-4 flex
                   items-center justify-between"
        >

            <h2 class="text-lg font-semibold">
                Daftar Tahun Ajaran
            </h2>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari..."
                class="rounded-lg border
                       px-3 py-2"
            >

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead>
                    <tr
                        class="border-b
                               text-sm text-zinc-500"
                    >
                        <th class="p-3">
                            Tahun Ajaran
                        </th>

                        <th class="p-3">
                            Mulai
                        </th>

                        <th class="p-3">
                            Selesai
                        </th>

                        <th class="p-3">
                            Status
                        </th>

                        @can('academic_years.manage')
                            <th class="p-3">
                                Aksi
                            </th>
                        @endcan
                    </tr>
                </thead>

                <tbody>

                    @forelse (
                        $academicYears
                        as $academicYear
                    )

                        <tr
                            class="border-b"
                            wire:key="academic-year-{{ $academicYear->id }}"
                        >

                            <td class="p-3 font-medium">
                                {{ $academicYear->name }}
                            </td>

                            <td class="p-3">
                                {{
                                    $academicYear
                                        ->start_date
                                        ->format('d-m-Y')
                                }}
                            </td>

                            <td class="p-3">
                                {{
                                    $academicYear
                                        ->end_date
                                        ->format('d-m-Y')
                                }}
                            </td>

                            <td class="p-3">

                                @if (
                                    $academicYear
                                        ->is_active
                                )

                                    <span
                                        class="rounded-full
                                               bg-green-100
                                               px-3 py-1
                                               text-xs
                                               text-green-700"
                                    >
                                        Aktif
                                    </span>

                                @else

                                    <span
                                        class="rounded-full
                                               bg-zinc-100
                                               px-3 py-1
                                               text-xs"
                                    >
                                        Tidak Aktif
                                    </span>

                                @endif

                            </td>

                            @can('academic_years.manage')

                                <td class="p-3">

                                    <div class="flex gap-2">

                                        <button
                                            type="button"
                                            wire:click="edit({{ $academicYear->id }})"
                                            class="rounded
                                                   border
                                                   px-3 py-1"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            onclick="
                                                if (
                                                    !confirm(
                                                        'Hapus tahun ajaran ini?'
                                                    )
                                                ) {
                                                    event.stopImmediatePropagation();
                                                }
                                            "
                                            wire:click="delete({{ $academicYear->id }})"
                                            class="rounded
                                                   border
                                                   px-3 py-1
                                                   text-red-600"
                                        >
                                            Hapus
                                        </button>

                                    </div>

                                </td>

                            @endcan

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="5"
                                class="p-6
                                       text-center
                                       text-zinc-500"
                            >
                                Belum ada tahun ajaran.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-4">
            {{ $academicYears->links() }}
        </div>

    </div>

</div>