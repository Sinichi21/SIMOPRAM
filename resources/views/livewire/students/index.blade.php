<div class="space-y-6">

    {{-- HEADER --}}
    <div>
        <h1
            class="text-2xl font-semibold
                   text-zinc-900
                   dark:text-white"
        >
            Data Siswa
        </h1>

        <p
            class="mt-1 text-sm
                   text-zinc-500"
        >
            Kelola identitas siswa,
            kelas, dan golongan Pramuka.
        </p>
    </div>


    {{-- FLASH --}}
    @if (session('success'))

        <div
            class="rounded-lg border
                   border-green-200
                   bg-green-50 p-4
                   text-sm text-green-700
                   dark:border-green-900
                   dark:bg-green-950
                   dark:text-green-300"
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- PERINGATAN MASTER DATA --}}
    @if (
        $academicYears->isEmpty()
        || $classrooms->isEmpty()
        || $scoutLevels->isEmpty()
    )

        <div
            class="rounded-xl border
                   border-amber-200
                   bg-amber-50 p-4
                   text-sm text-amber-700
                   dark:border-amber-900
                   dark:bg-amber-950
                   dark:text-amber-300"
        >
            Sebelum menambahkan siswa,
            pastikan Tahun Ajaran,
            Kelas, dan Golongan Pramuka
            sudah tersedia.
        </div>

    @endif


    @can('students.create')

        <form
            wire:submit="importCsv"
            class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Import Data Siswa</h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Unggah CSV maksimal 5 MB. Nama tahun ajaran, kelas, dan golongan harus sama dengan master data.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="downloadCsvTemplate"
                    class="rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
                >
                    Unduh Template CSV
                </button>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                <div class="flex-1">
                    <input
                        type="file"
                        wire:model="csvFile"
                        accept=".csv,text/csv"
                        class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    >

                    @error('csvFile')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="csvFile,importCsv"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 dark:bg-white dark:text-zinc-900"
                >
                    <span wire:loading.remove wire:target="importCsv">Import CSV</span>
                    <span wire:loading wire:target="csvFile,importCsv">Memproses...</span>
                </button>
            </div>

            @if ($importErrors !== [])
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300">
                    <div class="font-medium">Baris yang gagal:</div>

                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($importErrors as $importError)
                            <li wire:key="student-import-error-{{ $loop->index }}">
                                {{ $importError }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </form>

    @endcan


    {{-- FORM --}}
    @canany([
        'students.create',
        'students.update'
    ])

        <form
            wire:submit="save"
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div class="mb-6">

                <h2
                    class="text-lg font-semibold"
                >
                    {{ $editingId
                        ? 'Edit Siswa'
                        : 'Tambah Siswa'
                    }}
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Data siswa dan
                    penempatannya akan
                    disimpan bersamaan.
                </p>

            </div>


            {{-- IDENTITAS --}}
            <div
                class="mb-4 text-sm
                       font-semibold
                       text-zinc-700
                       dark:text-zinc-300"
            >
                Identitas Siswa
            </div>


            <div
                class="grid gap-4
                       md:grid-cols-2"
            >

                {{-- NIS --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        NIS
                    </label>

                    <input
                        type="text"
                        wire:model="nis"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('nis')
                        <p
                            class="mt-1
                                   text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- NISN --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        NISN
                    </label>

                    <input
                        type="text"
                        wire:model="nisn"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('nisn')
                        <p
                            class="mt-1
                                   text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- NAMA --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Nama Lengkap
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('name')
                        <p
                            class="mt-1
                                   text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- GENDER --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Jenis Kelamin
                    </label>

                    <select
                        wire:model="gender"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="">
                            -- Pilih --
                        </option>

                        <option value="L">
                            Laki-laki
                        </option>

                        <option value="P">
                            Perempuan
                        </option>
                    </select>

                    @error('gender')
                        <p
                            class="mt-1
                                   text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- TEMPAT LAHIR --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tempat Lahir
                    </label>

                    <input
                        type="text"
                        wire:model="birth_place"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                {{-- TANGGAL LAHIR --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tanggal Lahir
                    </label>

                    <input
                        type="date"
                        wire:model="birth_date"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('birth_date')
                        <p
                            class="mt-1
                                   text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- TELEPON SISWA --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Telepon Siswa
                    </label>

                    <input
                        type="text"
                        wire:model="phone"
                        placeholder="Opsional"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                {{-- TELEPON ORTU --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Telepon Orang Tua
                    </label>

                    <input
                        type="text"
                        wire:model="parent_phone"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                {{-- TANGGAL BERGABUNG --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tanggal Bergabung
                    </label>

                    <input
                        type="date"
                        wire:model="joined_at"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                {{-- STATUS --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Status
                    </label>

                    <select
                        wire:model="status"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="active">
                            Aktif
                        </option>

                        <option value="inactive">
                            Nonaktif
                        </option>

                        <option value="graduated">
                            Lulus
                        </option>

                        <option value="transferred">
                            Pindah
                        </option>
                    </select>
                </div>

            </div>


            {{-- ALAMAT --}}
            <div class="mt-4">

                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Alamat
                </label>

                <textarea
                    wire:model="address"
                    rows="3"
                    class="w-full rounded-lg
                           border
                           border-zinc-300
                           px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>

            </div>


            {{-- PENEMPATAN --}}
            <div
                class="mb-4 mt-8
                       border-t
                       border-zinc-200
                       pt-6
                       dark:border-zinc-800"
            >

                <div
                    class="text-sm
                           font-semibold
                           text-zinc-700
                           dark:text-zinc-300"
                >
                    Penempatan Siswa
                </div>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Penempatan disimpan
                    berdasarkan tahun ajaran.
                </p>

            </div>


            <div
                class="grid gap-4
                       md:grid-cols-3"
            >

                {{-- TAHUN AJARAN --}}
                <div>

                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Tahun Ajaran
                    </label>

                    <select
                        wire:model.live="
                            academic_year_id
                        "
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            -- Pilih --
                        </option>

                        @foreach (
                            $academicYears
                            as $academicYear
                        )

                            <option
                                value="{{ $academicYear->id }}"
                            >
                                {{ $academicYear->name }}

                                @if (
                                    $academicYear->is_active
                                )
                                    (Aktif)
                                @endif
                            </option>

                        @endforeach

                    </select>

                    @error('academic_year_id')
                        <p
                            class="mt-1
                                   text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- KELAS --}}
                <div>

                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Kelas
                    </label>

                    <select
                        wire:model="classroom_id"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            -- Pilih Kelas --
                        </option>

                        @foreach (
                            $classrooms
                            as $classroom
                        )

                            <option
                                value="{{ $classroom->id }}"
                            >
                                {{ $classroom->name }}
                            </option>

                        @endforeach

                    </select>

                    @error('classroom_id')
                        <p
                            class="mt-1
                                   text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- GOLONGAN --}}
                <div>

                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Golongan Pramuka
                    </label>

                    <select
                        wire:model="scout_level_id"
                        class="w-full rounded-lg
                               border
                               border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            -- Pilih Golongan --
                        </option>

                        @foreach (
                            $scoutLevels
                            as $scoutLevel
                        )

                            <option
                                value="{{ $scoutLevel->id }}"
                            >
                                {{ $scoutLevel->name }}
                            </option>

                        @endforeach

                    </select>

                    @error('scout_level_id')
                        <p
                            class="mt-1
                                   text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>


            {{-- ACTION --}}
            <div
                class="mt-6 flex gap-2"
            >

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    @disabled(
                        $academicYears->isEmpty()
                        ||
                        $classrooms->isEmpty()
                        ||
                        $scoutLevels->isEmpty()
                    )
                    class="rounded-lg
                           bg-zinc-900
                           px-4 py-2
                           text-sm font-medium
                           text-white
                           disabled:cursor-not-allowed
                           disabled:opacity-50
                           dark:bg-white
                           dark:text-zinc-900"
                >

                    <span
                        wire:loading.remove
                        wire:target="save"
                    >
                        {{ $editingId
                            ? 'Simpan Perubahan'
                            : 'Tambah Siswa'
                        }}
                    </span>

                    <span
                        wire:loading
                        wire:target="save"
                    >
                        Menyimpan...
                    </span>

                </button>


                @if ($editingId)

                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg
                               border
                               border-zinc-300
                               px-4 py-2
                               text-sm
                               dark:border-zinc-700"
                    >
                        Batal
                    </button>

                @endif

            </div>

        </form>

    @endcanany


    {{-- TABLE --}}
    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div class="mb-5 space-y-4">

            <div>

                <h2
                    class="text-lg font-semibold"
                >
                    Daftar Siswa
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    {{ $students->total() }}
                    siswa ditemukan.
                </p>

            </div>


            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div class="md:col-span-2 xl:col-span-1">
                    <label class="mb-1 block text-sm font-medium">
                        Pencarian
                    </label>

                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Nama, NIS, NISN, telepon, kelas..."
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Tahun Ajaran
                    </label>

                    <select
                        wire:model.live="filterAcademicYearId"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <option value="">Semua Tahun Ajaran</option>

                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}">
                                {{ $academicYear->name }}
                                {{ $academicYear->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Kelas
                    </label>

                    <select
                        wire:model.live="filterClassroomId"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <option value="">Semua Kelas</option>

                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}">
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Golongan
                    </label>

                    <select
                        wire:model.live="filterScoutLevelId"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <option value="">Semua Golongan</option>

                        @foreach ($scoutLevels as $scoutLevel)
                            <option value="{{ $scoutLevel->id }}">
                                {{ $scoutLevel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Status
                    </label>

                    <select
                        wire:model.live="filterStatus"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                        <option value="graduated">Lulus</option>
                        <option value="transferred">Pindah</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Jenis Kelamin
                    </label>

                    <div class="flex gap-2">
                        <select
                            wire:model.live="filterGender"
                            class="min-w-0 flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                        >
                            <option value="">Semua</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>

                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

        </div>


        <div class="overflow-x-auto">

            <table
                class="w-full text-left
                       text-sm"
            >

                <thead>

                    <tr
                        class="border-b
                               border-zinc-200
                               text-zinc-500
                               dark:border-zinc-800"
                    >

                        <th class="p-3">
                            Siswa
                        </th>

                        <th class="p-3">
                            NIS
                        </th>

                        <th class="p-3">
                            Kelas
                        </th>

                        <th class="p-3">
                            Golongan
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

                    @forelse (
                        $students as $student
                    )

                        @php
                            $currentEnrollment =
                                $student
                                    ->enrollments
                                    ->first();

                            $currentScoutLevel =
                                $student
                                    ->scoutLevelHistories
                                    ->first();
                        @endphp

                        <tr
                            wire:key="
                                student-{{ $student->id }}
                            "
                            class="border-b
                                   border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div
                                    class="font-medium"
                                >
                                    {{ $student->name }}
                                </div>

                                @if ($student->nisn)

                                    <div
                                        class="mt-1
                                               text-xs
                                               text-zinc-500"
                                    >
                                        NISN:
                                        {{ $student->nisn }}
                                    </div>

                                @endif

                            </td>


                            <td class="p-3">
                                {{ $student->nis }}
                            </td>


                            <td class="p-3">

                                {{ $currentEnrollment
                                    ?->classroom
                                    ?->name
                                    ?? '-'
                                }}

                            </td>


                            <td class="p-3">

                                {{ $currentScoutLevel
                                    ?->scoutLevel
                                    ?->name
                                    ?? '-'
                                }}

                            </td>


                            <td class="p-3">

                                @switch($student->status)

                                    @case('active')

                                        <span
                                            class="rounded-full
                                                   bg-green-100
                                                   px-2.5 py-1
                                                   text-xs
                                                   font-medium
                                                   text-green-700
                                                   dark:bg-green-950
                                                   dark:text-green-300"
                                        >
                                            Aktif
                                        </span>

                                        @break


                                    @case('inactive')

                                        <span
                                            class="rounded-full
                                                   bg-zinc-100
                                                   px-2.5 py-1
                                                   text-xs
                                                   font-medium
                                                   text-zinc-600
                                                   dark:bg-zinc-800
                                                   dark:text-zinc-300"
                                        >
                                            Nonaktif
                                        </span>

                                        @break


                                    @case('graduated')

                                        <span
                                            class="rounded-full
                                                   bg-blue-100
                                                   px-2.5 py-1
                                                   text-xs
                                                   font-medium
                                                   text-blue-700"
                                        >
                                            Lulus
                                        </span>

                                        @break


                                    @case('transferred')

                                        <span
                                            class="rounded-full
                                                   bg-amber-100
                                                   px-2.5 py-1
                                                   text-xs
                                                   font-medium
                                                   text-amber-700"
                                        >
                                            Pindah
                                        </span>

                                        @break

                                @endswitch

                            </td>


                            <td class="p-3">

                                <div
                                    class="flex
                                           flex-wrap gap-2"
                                >

                                    @can('student_accounts.manage')
                                        <a
                                            href="{{ route('student-accounts.manage', $student->id) }}"
                                            wire:navigate
                                            class="rounded-lg border border-zinc-300 px-3 py-1.5 dark:border-zinc-700"
                                        >
                                            {{ $student->user_id ? 'Kelola Akun' : 'Buat Akun' }}
                                        </a>
                                    @endcan

                                    @can(
                                        'students.update'
                                    )

                                        <button
                                            type="button"
                                            wire:click="
                                                edit(
                                                    {{ $student->id }}
                                                )
                                            "
                                            class="rounded-lg
                                                   border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   dark:border-zinc-700"
                                        >
                                            Edit
                                        </button>

                                    @endcan


                                    @can(
                                        'students.toggle'
                                    )

                                        @if (
                                            in_array(
                                                $student->status,
                                                [
                                                    'active',
                                                    'inactive'
                                                ],
                                                true
                                            )
                                        )

                                            <button
                                                type="button"
                                                wire:click="
                                                    toggleStatus(
                                                        {{ $student->id }}
                                                    )
                                                "
                                                wire:confirm="
                                                    Ubah status siswa ini?
                                                "
                                                class="rounded-lg
                                                       border
                                                       border-zinc-300
                                                       px-3 py-1.5
                                                       dark:border-zinc-700"
                                            >
                                                {{ $student->status ===
                                                    'active'
                                                        ? 'Nonaktifkan'
                                                        : 'Aktifkan'
                                                }}
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
                                class="p-8
                                       text-center
                                       text-zinc-500"
                            >
                                Belum ada data siswa.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            {{ $students->links() }}
        </div>

    </div>

</div>
