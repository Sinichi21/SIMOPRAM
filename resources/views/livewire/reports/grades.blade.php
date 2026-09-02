<div class="space-y-6">

    <div
        class="flex flex-col gap-4
               lg:flex-row
               lg:items-end
               lg:justify-between"
    >
        <div>
            <h1 class="text-2xl font-semibold">
                Rekap Nilai
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                Rekap hasil penilaian Pramuka
                berdasarkan periode dan kelas.
            </p>
        </div>

        @can('reports.export')

            <div
                class="flex flex-wrap gap-2"
            >

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
                        'reports.grades.pdf',
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


        @error('export')

            <div
                class="mt-2 text-sm
                    text-red-600"
            >
                {{ $message }}
            </div>

        @enderror
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
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
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
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
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
                Kelas
            </label>

            <select
                wire:model.live="classroomId"
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
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
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >
        </div>

    </div>


    @if (! $selectedConfig)

        <div
            class="rounded-xl border
                   border-amber-200
                   bg-amber-50 p-6
                   text-sm text-amber-700
                   dark:border-amber-900
                   dark:bg-amber-950
                   dark:text-amber-300"
        >
            Belum ada konfigurasi penilaian aktif
            untuk tahun ajaran dan semester ini.
        </div>

    @else

        <div>
            <div class="font-semibold">
                {{ $selectedConfig->name }}
            </div>

            <div class="text-sm text-zinc-500">
                {{ $selectedConfig->academicYear?->name }}
                ·
                {{ $selectedConfig->semester?->name }}
            </div>
        </div>


        <div
            class="overflow-x-auto rounded-xl
                   border border-zinc-200
                   bg-white
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            @if (
                $syncStatus
                &&
                (
                    $syncStatus['attendance']['is_stale']
                    ||
                    $syncStatus['final']['is_stale']
                )
            )

                <div
                    class="rounded-xl border
                        border-amber-300
                        bg-amber-50
                        px-4 py-3
                        text-sm
                        text-amber-800
                        dark:border-amber-900
                        dark:bg-amber-950/40
                        dark:text-amber-300"
                >

                    <div class="font-semibold">
                        Data nilai belum sinkron
                    </div>

                    <p class="mt-1">
                        Sebagian nilai belum menggunakan
                        konfigurasi penilaian terbaru.
                        Lakukan sinkronisasi sebelum
                        mencetak atau mengekspor laporan.
                    </p>


                    <div
                        class="mt-3 flex flex-wrap gap-4
                            text-xs"
                    >

                        <span>
                            Nilai Kehadiran:
                            <strong>
                                {{ number_format(
                                    $syncStatus[
                                        'attendance'
                                    ][
                                        'stale_count'
                                    ]
                                ) }}
                            </strong>
                            belum sinkron
                        </span>


                        <span>
                            Nilai Akhir:
                            <strong>
                                {{ number_format(
                                    $syncStatus[
                                        'final'
                                    ][
                                        'stale_count'
                                    ]
                                ) }}
                            </strong>
                            belum sinkron
                        </span>

                    </div>

                </div>

            @endif


            @error('export')

                <div
                    class="rounded-lg border
                        border-red-200
                        bg-red-50
                        px-4 py-3
                        text-sm
                        text-red-700
                        dark:border-red-900
                        dark:bg-red-950/40
                        dark:text-red-300"
                >
                    {{ $message }}
                </div>

            @enderror

            <table class="w-full text-left text-sm">

                <thead>
                    <tr
                        class="border-b
                               border-zinc-200
                               text-zinc-500
                               dark:border-zinc-800"
                    >
                        <th class="whitespace-nowrap p-3">
                            NIS
                        </th>

                        <th class="min-w-48 p-3">
                            Nama
                        </th>

                        <th class="p-3">
                            Kelas
                        </th>


                        @foreach (
                            $selectedConfig->items
                            as $item
                        )
                            <th class="min-w-32 p-3">
                                {{ $item->factor->name }}

                                <div class="text-xs font-normal">
                                    {{ number_format(
                                        $item->weight,
                                        2
                                    ) }}%
                                </div>
                            </th>
                        @endforeach


                        <th class="p-3">
                            Akhir
                        </th>

                        <th class="p-3">
                            Predikat
                        </th>

                        <th class="min-w-40 p-3">
                            Deskripsi
                        </th>
                    </tr>
                </thead>


                <tbody>

                    @forelse ($students as $student)

                        @php
                            $studentScores =
                                $scores->get(
                                    $student->id,
                                    collect()
                                );

                            $final =
                                $finalGrades->get(
                                    $student->id
                                );

                            $enrollment =
                                $student
                                    ->enrollments
                                    ->first();
                        @endphp


                        <tr
                            wire:key="grade-report-{{ $student->id }}"
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


                            @foreach (
                                $selectedConfig->items
                                as $item
                            )

                                @php
                                    $score =
                                        $studentScores->get(
                                            $item
                                                ->assessment_factor_id
                                        );
                                @endphp

                                <td class="p-3">
                                    {{ $score
                                        ? number_format(
                                            $score->score,
                                            2
                                        )
                                        : '-'
                                    }}
                                </td>

                            @endforeach


                            <td class="p-3 font-semibold">
                                {{ $final
                                    ? number_format(
                                        $final->final_score,
                                        2
                                    )
                                    : '-'
                                }}
                            </td>

                            <td class="p-3 font-semibold">
                                {{ $final
                                    ?->letter_grade ?? '-'
                                }}
                            </td>

                            <td class="p-3">
                                {{ $final
                                    ?->description ?? '-'
                                }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="20"
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Tidak ada data siswa.
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>

        </div>

    @endif

</div>