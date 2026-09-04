<div class="space-y-6">

    @if (session('success'))

        <div
            class="rounded-lg border border-green-200
                   bg-green-50 p-4 text-sm text-green-700
                   dark:border-green-900 dark:bg-green-950
                   dark:text-green-300"
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- =====================================================
    HEADER
    ====================================================== --}}

    <div>
        <h1
            class="text-2xl font-semibold
                   text-zinc-900 dark:text-white"
        >
            Regu / Barung
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Kelola kelompok Pramuka dan keanggotaan siswa.
        </p>
    </div>


    {{-- =====================================================
    FORM UNIT
    ====================================================== --}}

    @can('scout_units.manage')

        <form
            wire:submit="saveUnit"
            class="rounded-xl border border-zinc-200
                   bg-white p-6 shadow-sm
                   dark:border-zinc-800 dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">

                {{ $editingId
                    ? 'Edit Regu / Barung'
                    : 'Tambah Regu / Barung'
                }}

            </h2>


            <div class="grid gap-4 md:grid-cols-3">

                {{-- Tahun Ajaran --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Tahun Ajaran
                    </label>

                    <select
                        wire:model="academic_year_id"
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

                                @if ($year->is_active)
                                    (Aktif)
                                @endif
                            </option>

                        @endforeach

                    </select>

                    @error('academic_year_id')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Golongan --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Golongan
                    </label>

                    <select
                        wire:model="scout_level_id"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            -- Pilih --
                        </option>

                        @foreach ($scoutLevels as $level)

                            <option value="{{ $level->id }}">
                                {{ $level->name }}
                            </option>

                        @endforeach

                    </select>

                    @error('scout_level_id')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Nama --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Nama
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="Contoh: Regu Garuda"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('name')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>


            <div class="mt-4">

                <label
                    class="mb-1 block text-sm font-medium"
                >
                    Keterangan
                </label>

                <textarea
                    wire:model="description"
                    rows="3"
                    class="w-full rounded-lg border
                           border-zinc-300 px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>

            </div>


            <label class="mt-4 flex items-center gap-2">

                <input
                    type="checkbox"
                    wire:model="is_active"
                >

                <span class="text-sm">
                    Unit aktif
                </span>

            </label>


            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900
                           px-4 py-2 text-sm font-medium
                           text-white
                           dark:bg-white dark:text-zinc-900"
                >
                    {{ $editingId
                        ? 'Simpan Perubahan'
                        : 'Tambah'
                    }}
                </button>


                @if ($editingId)

                    <button
                        type="button"
                        wire:click="cancelUnitEdit"
                        class="rounded-lg border
                               border-zinc-300 px-4 py-2
                               text-sm dark:border-zinc-700"
                    >
                        Batal
                    </button>

                @endif

            </div>

        </form>

    @endcan


    {{-- =====================================================
    FILTER
    ====================================================== --}}

    <div
        class="rounded-xl border border-zinc-200
               bg-white p-6 shadow-sm
               dark:border-zinc-800 dark:bg-zinc-900"
    >

        <div
            class="mb-5 grid gap-3
                   md:grid-cols-3"
        >

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari Regu / Barung..."
                class="rounded-lg border border-zinc-300
                       px-3 py-2 text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >


            <select
                wire:model.live="filterAcademicYearId"
                class="rounded-lg border border-zinc-300
                       px-3 py-2 text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

                <option value="">
                    Semua Tahun Ajaran
                </option>

                @foreach ($academicYears as $year)

                    <option value="{{ $year->id }}">
                        {{ $year->name }}
                    </option>

                @endforeach

            </select>


            <select
                wire:model.live="filterScoutLevelId"
                class="rounded-lg border border-zinc-300
                       px-3 py-2 text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

                <option value="">
                    Semua Golongan
                </option>

                @foreach ($scoutLevels as $level)

                    <option value="{{ $level->id }}">
                        {{ $level->name }}
                    </option>

                @endforeach

            </select>

        </div>


        {{-- =================================================
        UNIT TABLE
        ================================================== --}}

        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead>

                    <tr
                        class="border-b border-zinc-200
                               text-zinc-500
                               dark:border-zinc-800"
                    >
                        <th class="p-3">
                            Nama
                        </th>

                        <th class="p-3">
                            Golongan
                        </th>

                        <th class="p-3">
                            Tahun
                        </th>

                        <th class="p-3">
                            Pemimpin
                        </th>

                        <th class="p-3">
                            Anggota
                        </th>

                        <th class="p-3">
                            Status
                        </th>

                        <th class="p-3">
                            Aksi
                        </th>
                    </tr>

                </thead>


                <tbody>

                    @forelse ($units as $unit)

                        <tr
                            wire:key="unit-{{ $unit->id }}"
                            class="border-b border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div class="font-medium">
                                    {{ $unit->name }}
                                </div>

                                <div
                                    class="mt-1 text-xs
                                           uppercase text-zinc-500"
                                >
                                    {{ $unit->unit_type }}
                                </div>

                            </td>


                            <td class="p-3">
                                {{ $unit->scoutLevel?->name ?? '-' }}
                            </td>


                            <td class="p-3">
                                {{ $unit->academicYear?->name ?? '-' }}
                            </td>


                            <td class="p-3">
                                {{ $unit->leader?->name ?? '-' }}
                            </td>


                            <td class="p-3">
                                {{ $unit->active_members_count }}
                            </td>


                            <td class="p-3">

                                {{ $unit->is_active
                                    ? 'Aktif'
                                    : 'Nonaktif'
                                }}

                            </td>


                            <td class="p-3">

                                <div class="flex flex-wrap gap-2">

                                    <button
                                        type="button"
                                        wire:click="selectUnit({{ $unit->id }})"
                                        class="rounded-lg bg-zinc-900
                                               px-3 py-1.5 text-white
                                               dark:bg-white
                                               dark:text-zinc-900"
                                    >
                                        Anggota
                                    </button>


                                    @can('scout_units.manage')

                                        <button
                                            type="button"
                                            wire:click="editUnit({{ $unit->id }})"
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   dark:border-zinc-700"
                                        >
                                            Edit
                                        </button>


                                        <button
                                            type="button"
                                            wire:click="
                                                toggleUnitStatus(
                                                    {{ $unit->id }}
                                                )
                                            "
                                            wire:confirm="
                                                Ubah status unit ini?
                                            "
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   dark:border-zinc-700"
                                        >
                                            {{ $unit->is_active
                                                ? 'Nonaktifkan'
                                                : 'Aktifkan'
                                            }}
                                        </button>

                                    @endcan

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Belum ada Regu / Barung.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            {{ $units->links() }}
        </div>

    </div>


    {{-- =====================================================
    MEMBER MANAGEMENT
    ====================================================== --}}

    @if ($selectedUnit)

        <div
            class="rounded-xl border border-zinc-200
                   bg-white p-6 shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div class="mb-6">

                <h2 class="text-lg font-semibold">
                    Anggota {{ $selectedUnit->name }}
                </h2>

                <p class="mt-1 text-sm text-zinc-500">

                    {{ $selectedUnit->scoutLevel?->name }}

                    ·

                    {{ $selectedUnit->academicYear?->name }}

                </p>

            </div>


            @can('scout_units.manage')

                <div
                    class="mb-6 grid gap-3
                           md:grid-cols-4"
                >

                    {{-- Student --}}
                    <div
                        wire:key="member-student-selector-{{ $selectedUnit->id }}"
                        x-data="{
                            open: false,
                            search: '',
                            selectStudent(id, label) {
                                $wire.set('memberStudentId', id);
                                this.search = label;
                                this.open = false;
                            },
                        }"
                        x-on:click.outside="open = false"
                        x-on:member-added.window="search = ''; open = false"
                        class="relative"
                    >
                        <input
                            type="search"
                            x-model="search"
                            x-on:focus="open = true"
                            x-on:input="open = true; $wire.set('memberStudentId', null)"
                            x-on:keydown.escape="open = false"
                            placeholder="Cari nama atau NIS siswa..."
                            autocomplete="off"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                        >

                        <div
                            x-show="open"
                            x-cloak
                            class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                        >
                            @forelse ($eligibleStudents as $student)
                                @php
                                    $studentLabel = $student->name.' - '.$student->nis;
                                @endphp

                                <button
                                    type="button"
                                    x-show="@js(mb_strtolower($studentLabel)).includes(search.toLowerCase())"
                                    x-on:click="selectStudent({{ $student->id }}, @js($studentLabel))"
                                    class="block w-full rounded-md px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                >
                                    <span class="font-medium">{{ $student->name }}</span>
                                    <span class="text-zinc-500">— {{ $student->nis }}</span>
                                </button>
                            @empty
                                <p class="px-3 py-2 text-sm text-zinc-500">
                                    Tidak ada siswa yang dapat ditambahkan.
                                </p>
                            @endforelse
                        </div>

                    </div>


                    {{-- Position --}}
                    <select
                        wire:model="memberPosition"
                        class="rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        @foreach (
                            $positionOptions
                            as $value => $label
                        )

                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>

                        @endforeach

                    </select>


                    {{-- Joined --}}
                    <input
                        type="date"
                        wire:model="memberJoinedAt"
                        class="rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >


                    <button
                        type="button"
                        wire:click="addMember"
                        class="rounded-lg bg-zinc-900
                               px-4 py-2 text-sm
                               font-medium text-white
                               dark:bg-white
                               dark:text-zinc-900"
                    >
                        Tambah Anggota
                    </button>

                </div>


                @error('memberStudentId')
                    <p class="mb-4 text-sm text-red-500">
                        {{ $message }}
                    </p>
                @enderror

            @endcan


            {{-- MEMBER TABLE --}}
            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead>

                        <tr
                            class="border-b border-zinc-200
                                   text-zinc-500
                                   dark:border-zinc-800"
                        >
                            <th class="p-3">
                                Nama
                            </th>

                            <th class="p-3">
                                NIS
                            </th>

                            <th class="p-3">
                                Jabatan
                            </th>

                            <th class="p-3">
                                Bergabung
                            </th>

                            <th class="p-3">
                                Aksi
                            </th>
                        </tr>

                    </thead>


                    <tbody>

                        @forelse (
                            $selectedUnit->memberships
                            as $membership
                        )

                            <tr
                                wire:key="
                                    membership-{{ $membership->id }}
                                "
                                class="border-b
                                       border-zinc-100
                                       dark:border-zinc-800"
                            >

                                <td class="p-3">
                                    {{ $membership->student?->name }}
                                </td>


                                <td class="p-3">
                                    {{ $membership->student?->nis }}
                                </td>


                                <td class="p-3">

                                    @can('scout_units.manage')

                                        <select
                                            wire:change="
                                                changeMemberPosition(
                                                    {{ $membership->id }},
                                                    $event.target.value
                                                )
                                            "
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-2 py-1
                                                   dark:border-zinc-700
                                                   dark:bg-zinc-800"
                                        >

                                            @foreach (
                                                $positionOptions
                                                as $value => $label
                                            )

                                                <option
                                                    value="{{ $value }}"
                                                    @selected(
                                                        $membership->position
                                                        ===
                                                        $value
                                                    )
                                                >
                                                    {{ $label }}
                                                </option>

                                            @endforeach

                                        </select>

                                    @else

                                        {{ $positionOptions[
                                            $membership->position
                                        ] ?? $membership->position }}

                                    @endcan

                                </td>


                                <td class="p-3">

                                    {{ $membership
                                        ->joined_at
                                        ?->format('d-m-Y')
                                        ?? '-'
                                    }}

                                </td>


                                <td class="p-3">

                                    @can('scout_units.manage')

                                        <button
                                            type="button"
                                            wire:click="
                                                removeMember(
                                                    {{ $membership->id }}
                                                )
                                            "
                                            wire:confirm="
                                                Keluarkan siswa
                                                dari unit ini?
                                            "
                                            class="rounded-lg border
                                                   border-red-300
                                                   px-3 py-1.5
                                                   text-red-600"
                                        >
                                            Keluarkan
                                        </button>

                                    @endcan

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="p-8 text-center
                                           text-zinc-500"
                                >
                                    Belum ada anggota.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    @endif

</div>
