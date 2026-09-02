<div class="space-y-6">

    <div
        class="flex flex-col gap-4
               lg:flex-row
               lg:items-end
               lg:justify-between"
    >

        <div>
            <h1 class="text-2xl font-semibold">
                Rekap Absensi
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                Rekap kehadiran siswa berdasarkan
                tahun ajaran, semester, dan kelas.
            </p>
        </div>


        @can('reports.export')

            <div class="flex flex-wrap gap-2">

                <button
                    type="button"
                    wire:click="exportCsv"
                    wire:loading.attr="disabled"
                    wire:target="exportCsv"
                    class="rounded-lg border
                        border-zinc-300
                        px-4 py-2
                        text-sm font-medium
                        hover:bg-zinc-50
                        disabled:opacity-50
                        dark:border-zinc-700
                        dark:hover:bg-zinc-800"
                >
                    <span
                        wire:loading.remove
                        wire:target="exportCsv"
                    >
                        Export CSV
                    </span>

                    <span
                        wire:loading
                        wire:target="exportCsv"
                    >
                        Memproses...
                    </span>
                </button>


                <button
                    type="button"
                    wire:click="exportExcel"
                    wire:loading.attr="disabled"
                    wire:target="exportExcel"
                    class="rounded-lg border
                        border-zinc-300
                        px-4 py-2
                        text-sm font-medium
                        hover:bg-zinc-50
                        disabled:opacity-50
                        dark:border-zinc-700
                        dark:hover:bg-zinc-800"
                >
                    <span
                        wire:loading.remove
                        wire:target="exportExcel"
                    >
                        Export Excel
                    </span>

                    <span
                        wire:loading
                        wire:target="exportExcel"
                    >
                        Memproses...
                    </span>
                </button>

                <a
                    href="{{ route(
                        'reports.attendance.pdf',
                        array_filter([
                            'academic_year_id' =>
                                $academicYearId,

                            'semester_id' =>
                                $semesterId,

                            'classroom_id' =>
                                $classroomId,
                        ])
                    ) }}"
                    target="_blank"
                    class="inline-flex items-center
                        rounded-lg
                        bg-zinc-900
                        px-4 py-2
                        text-sm font-medium
                        text-white
                        hover:bg-zinc-800
                        dark:bg-white
                        dark:text-zinc-900"
                >
                    Export PDF
                </a>

            </div>

        @endcan

    </div>


    <div
        class="grid gap-4 rounded-xl
               border border-zinc-200
               bg-white p-5
               md:grid-cols-4
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div>

            <label class="mb-1 block text-sm font-medium">
                Tahun Ajaran
            </label>

            <select
                wire:model.live="academicYearId"
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
                wire:model.live="semesterId"
                class="w-full rounded-lg border
                       border-zinc-300 px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >
                <option value="">
                    Semua Semester
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
                Kelas
            </label>

            <select
                wire:model.live="classroomId"
                class="w-full rounded-lg border
                       border-zinc-300 px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >
                <option value="">
                    Semua Kelas
                </option>

                @foreach ($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">
                        {{ $classroom->name }}
                    </option>
                @endforeach
            </select>

        </div>


        <div>

            <label class="mb-1 block text-sm font-medium">
                Cari Siswa
            </label>

            <input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Nama / NIS..."
                class="w-full rounded-lg border
                       border-zinc-300 px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

        </div>

    </div>


    <div
        class="grid gap-4
               sm:grid-cols-2
               lg:grid-cols-3"
    >

        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-5
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >
            <div class="text-sm text-zinc-500">
                Sesi Absensi
            </div>

            <div class="mt-2 text-3xl font-semibold">
                {{ $sessionCount }}
            </div>
        </div>


        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-5
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >
            <div class="text-sm text-zinc-500">
                Siswa
            </div>

            <div class="mt-2 text-3xl font-semibold">
                {{ $rows->count() }}
            </div>
        </div>

    </div>


    <div
        class="overflow-x-auto
               rounded-xl border
               border-zinc-200
               bg-white
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <table class="w-full text-left text-sm">

            <thead>

                <tr
                    class="border-b
                           border-zinc-200
                           text-zinc-500
                           dark:border-zinc-800"
                >

                    <th class="p-3">
                        NIS
                    </th>

                    <th class="min-w-48 p-3">
                        Nama
                    </th>

                    <th class="p-3">
                        Kelas
                    </th>

                    <th class="p-3">
                        Pertemuan
                    </th>

                    <th class="p-3">
                        Hadir
                    </th>

                    <th class="p-3">
                        Terlambat
                    </th>

                    <th class="p-3">
                        Sakit
                    </th>

                    <th class="p-3">
                        Izin
                    </th>

                    <th class="p-3">
                        Alpa
                    </th>

                    <th class="p-3">
                        Belum Dicatat
                    </th>

                    <th class="p-3">
                        % Hadir
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse ($rows as $row)

                    @php
                        $student =
                            $row['student'];

                        $enrollment =
                            $student
                                ->enrollments
                                ->first();
                    @endphp


                    <tr
                        wire:key="attendance-report-{{ $student->id }}"
                        class="border-b
                               border-zinc-100
                               dark:border-zinc-800"
                    >

                        <td class="p-3">
                            {{ $student->nis }}
                        </td>

                        <td class="p-3 font-medium">
                            {{ $student->name }}
                        </td>

                        <td class="p-3">
                            {{ $enrollment
                                ?->classroom
                                ?->name ?? '-'
                            }}
                        </td>

                        <td class="p-3">
                            {{ $row['participants'] }}
                        </td>

                        <td class="p-3">
                            {{ $row['present'] }}
                        </td>

                        <td class="p-3">
                            {{ $row['late'] }}
                        </td>

                        <td class="p-3">
                            {{ $row['sick'] }}
                        </td>

                        <td class="p-3">
                            {{ $row['excused'] }}
                        </td>

                        <td class="p-3">
                            {{ $row['absent'] }}
                        </td>

                        <td class="p-3">
                            {{ $row['unrecorded'] }}
                        </td>

                        <td class="p-3 font-semibold">

                            {{ number_format(
                                $row[
                                    'presence_percentage'
                                ],
                                2
                            ) }}%

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="11"
                            class="p-8 text-center
                                   text-zinc-500"
                        >
                            Tidak ada data absensi.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>