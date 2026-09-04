<div class="space-y-6">

    @if (session('success'))
        <div
            class="rounded-lg border border-green-200
                   bg-green-50 p-4 text-sm text-green-700"
        >
            {{ session('success') }}
        </div>
    @endif


    <div>
        <h1 class="text-2xl font-semibold">
            Agenda / Kegiatan
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Kelola agenda kegiatan Pramuka
            pada sekolah aktif.
        </p>
    </div>


    @canany([
        'activities.create',
        'activities.update'
    ])

        <form
            wire:submit="save"
            class="rounded-xl border
                   border-zinc-200 bg-white
                   p-6 shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">
                {{ $editingId
                    ? 'Edit Agenda'
                    : 'Tambah Agenda'
                }}
            </h2>


            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Judul Kegiatan
                    </label>

                    <input
                        type="text"
                        wire:model="title"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('title')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Jenis Kegiatan
                    </label>

                    <select
                        wire:model="activity_type"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="regular">
                            Latihan Rutin
                        </option>

                        <option value="training">
                            Pelatihan
                        </option>

                        <option value="ceremony">
                            Upacara
                        </option>

                        <option value="camp">
                            Perkemahan
                        </option>

                        <option value="competition">
                            Lomba
                        </option>

                        <option value="service">
                            Bakti Sosial
                        </option>

                        <option value="other">
                            Lainnya
                        </option>
                    </select>
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Tahun Ajaran
                    </label>

                    <select
                        wire:model.live="academic_year_id"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="">
                            -- Pilih --
                        </option>

                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}">
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Semester
                    </label>

                    <select
                        wire:model="semester_id"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="">
                            -- Pilih --
                        </option>

                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}">
                                {{ $semester->name }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Mulai
                    </label>

                    <input
                        type="datetime-local"
                        wire:model="start_at"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Selesai
                    </label>

                    <input
                        type="datetime-local"
                        wire:model="end_at"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Lokasi
                    </label>

                    <input
                        type="text"
                        wire:model="location"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Status
                    </label>

                    <select
                        wire:model="status"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="draft">
                            Draft
                        </option>

                        <option value="published">
                            Dipublikasikan
                        </option>

                        <option value="completed">
                            Selesai
                        </option>

                        <option value="cancelled">
                            Dibatalkan
                        </option>
                    </select>
                </div>

            </div>


            <div class="mt-4">

                <label class="mb-1 block text-sm font-medium">
                    Deskripsi
                </label>

                <textarea
                    wire:model="description"
                    rows="4"
                    class="w-full rounded-lg border
                           border-zinc-300 px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>

            </div>


            <div class="mt-4">

                <label class="mb-2 block text-sm font-medium">
                    Golongan Peserta
                </label>

                <p class="mb-2 text-xs text-zinc-500">
                    Kosongkan pilihan jika kegiatan berlaku untuk semua golongan.
                </p>

                <div class="grid gap-2 md:grid-cols-3">

                    @foreach ($scoutLevels as $scoutLevel)
                        <label
                            wire:key="activity-level-{{ $scoutLevel->id }}"
                            class="flex items-center gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700"
                        >
                            <input
                                type="checkbox"
                                wire:model="scout_level_ids"
                                value="{{ $scoutLevel->id }}"
                            >

                            <span class="text-sm">{{ $scoutLevel->name }}</span>
                        </label>
                    @endforeach

                </div>

                @error('scout_level_ids.*')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror

            </div>


            <div class="mt-4">

                <label class="mb-2 block text-sm font-medium">
                    Pembina
                </label>

                <div class="grid gap-2 md:grid-cols-3">

                    @foreach ($coaches as $coach)

                        <label
                            class="flex items-center gap-2
                                   rounded-lg border
                                   border-zinc-200 p-3
                                   dark:border-zinc-700"
                        >

                            <input
                                type="checkbox"
                                wire:model="coach_ids"
                                value="{{ $coach->id }}"
                            >

                            <span class="text-sm">
                                {{ $coach->name }}
                            </span>

                        </label>

                    @endforeach

                </div>

            </div>


            <label class="mt-5 flex items-center gap-2">

                <input
                    type="checkbox"
                    wire:model="is_public"
                >

                <span class="text-sm">
                    Tampilkan agenda ini pada halaman publik
                </span>

            </label>


            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900
                           px-4 py-2 text-sm font-medium
                           text-white dark:bg-white
                           dark:text-zinc-900"
                >
                    {{ $editingId
                        ? 'Simpan Perubahan'
                        : 'Tambah Agenda'
                    }}
                </button>


                @if ($editingId)

                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg border
                               border-zinc-300
                               px-4 py-2 text-sm"
                    >
                        Batal
                    </button>

                @endif

            </div>

        </form>

    @endcanany


    <div
        class="rounded-xl border border-zinc-200
               bg-white p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="mb-5 flex flex-col gap-3
                   md:flex-row md:justify-between"
        >

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari agenda..."
                class="rounded-lg border
                       border-zinc-300 px-3 py-2"
            >

            <select
                wire:model.live="filterStatus"
                class="rounded-lg border
                       border-zinc-300 px-3 py-2"
            >
                <option value="">
                    Semua Status
                </option>

                <option value="draft">
                    Draft
                </option>

                <option value="published">
                    Dipublikasikan
                </option>

                <option value="completed">
                    Selesai
                </option>

                <option value="cancelled">
                    Dibatalkan
                </option>
            </select>

            <select
                wire:model.live="filterScoutLevelId"
                class="rounded-lg border border-zinc-300 px-3 py-2"
            >
                <option value="">Semua Golongan</option>

                @foreach ($scoutLevels as $scoutLevel)
                    <option value="{{ $scoutLevel->id }}">
                        {{ $scoutLevel->name }}
                    </option>
                @endforeach
            </select>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead>
                    <tr class="border-b text-zinc-500">
                        <th class="p-3">Agenda</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Lokasi</th>
                        <th class="p-3">Pembina</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($activities as $activity)

                        <tr
                            wire:key="activity-{{ $activity->id }}"
                            class="border-b
                                   border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">
                                <div class="font-medium">
                                    {{ $activity->title }}
                                </div>

                                <div class="text-xs text-zinc-500">
                                    {{ $activity->academicYear?->name }}
                                </div>

                                <div class="text-xs text-zinc-500">
                                    {{ $activity->scoutLevels->pluck('name')->join(', ') ?: 'Semua Golongan' }}
                                </div>
                            </td>


                            <td class="p-3">
                                {{ $activity->start_at->format('d-m-Y H:i') }}
                            </td>


                            <td class="p-3">
                                {{ $activity->location ?: '-' }}
                            </td>


                            <td class="p-3">
                                {{ $activity->coaches
                                    ->pluck('name')
                                    ->join(', ')
                                    ?: '-'
                                }}
                            </td>


                            <td class="p-3">
                                {{ ucfirst($activity->status) }}
                            </td>


                            <td class="p-3">

                                <div class="flex gap-2">

                                    @can('activities.update')

                                        <button
                                            type="button"
                                            wire:click="edit({{ $activity->id }})"
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5"
                                        >
                                            Edit
                                        </button>

                                    @endcan

                                    @can('journals.view')

                                        <a
                                            href="{{ route(
                                                'journals.manage',
                                                $activity->id
                                            ) }}"
                                            wire:navigate
                                            class="rounded-lg border
                                                border-zinc-300
                                                px-3 py-1.5
                                                text-sm
                                                dark:border-zinc-700"
                                        >
                                            Jurnal
                                        </a>

                                    @endcan

                                    @can('activities.cancel')

                                        @if (
                                            $activity->status !==
                                            'cancelled'
                                        )

                                            <button
                                                type="button"
                                                wire:click="
                                                    cancelActivity(
                                                        {{ $activity->id }}
                                                    )
                                                "
                                                wire:confirm="
                                                    Batalkan agenda ini?
                                                "
                                                class="rounded-lg
                                                       border
                                                       border-red-300
                                                       px-3 py-1.5
                                                       text-red-600"
                                            >
                                                Batalkan
                                            </button>

                                        @endif

                                    @endcan

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="6"
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Belum ada agenda kegiatan.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            {{ $activities->links() }}
        </div>

    </div>

</div>
