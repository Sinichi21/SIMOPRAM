<div class="space-y-6">

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


    <div
        class="flex flex-col gap-4
               lg:flex-row
               lg:items-end
               lg:justify-between"
    >

        <div>
            <h1 class="text-2xl font-semibold">
                Input Nilai Siswa
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                Input nilai manual dan hitung nilai
                kehadiran secara otomatis.
            </p>
        </div>


        <div class="flex flex-wrap gap-2">

            @can('assessments.calculate')

                <button
                    wire:click="refreshAttendanceScores"
                    type="button"
                    class="rounded-lg border
                           border-zinc-300
                           px-4 py-2 text-sm"
                >
                    Perbarui Nilai Kehadiran
                </button>


                <button
                    wire:click="calculateAll"
                    wire:confirm="
                        Hitung nilai akhir seluruh siswa?
                    "
                    type="button"
                    class="rounded-lg bg-zinc-900
                           px-4 py-2 text-sm
                           font-medium text-white
                           dark:bg-white
                           dark:text-zinc-900"
                >
                    Hitung Semua Nilai
                </button>

            @endcan

        </div>

    </div>


    <div
        class="grid gap-4
               md:grid-cols-3"
    >

        <div>

            <label class="mb-1 block text-sm font-medium">
                Konfigurasi Penilaian
            </label>

            <select
                wire:model.live="configId"
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

                <option value="">
                    -- Pilih Konfigurasi --
                </option>

                @foreach ($configs as $config)

                    <option value="{{ $config->id }}">
                        {{ $config->name }}

                        @if ($config->is_active)
                            (Aktif)
                        @endif
                    </option>

                @endforeach

            </select>

        </div>


        <div>

            <label class="mb-1 block text-sm font-medium">
                Golongan
            </label>

            <select
                wire:model.live="scoutLevelId"
                class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
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


    @if ($selectedConfig)

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
                            Siswa
                        </th>


                        @foreach (
                            $selectedConfig->items
                            as $item
                        )

                            <th class="min-w-36 p-3">

                                {{ $item->factor->name }}

                                <div class="text-xs font-normal">
                                    Bobot
                                    {{ number_format(
                                        $item->weight,
                                        2
                                    ) }}%

                                    ·

                                    {{ $item->factor->source_type === 'attendance'
                                        ? 'Otomatis'
                                        : 'Manual'
                                    }}
                                </div>

                            </th>

                        @endforeach


                        <th class="p-3">
                            Nilai Akhir
                        </th>

                        <th class="p-3">
                            Predikat
                        </th>

                        <th class="p-3">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse ($students as $student)

                        @php
                            $final =
                                $finalGrades->get(
                                    $student->id
                                );
                        @endphp


                        <tr
                            wire:key="score-student-{{ $student->id }}"
                            class="border-b
                                   border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div class="font-medium">
                                    {{ $student->name }}
                                </div>

                                <div class="text-xs text-zinc-500">
                                    {{ $student->nis }}
                                </div>

                            </td>


                            @foreach (
                                $selectedConfig->items
                                as $item
                            )

                                <td class="p-3">

                                    @if (
                                        $item->factor->source_type
                                        ===
                                        'attendance'
                                    )

                                        <div
                                            class="rounded-lg
                                                   bg-zinc-100
                                                   px-3 py-2
                                                   font-semibold
                                                   dark:bg-zinc-800"
                                        >
                                            {{ number_format(
                                                $scores[
                                                    $student->id
                                                ][
                                                    $item->assessment_factor_id
                                                ] ?? 0,
                                                2
                                            ) }}
                                        </div>

                                    @else

                                        <input
                                            wire:model="
                                                scores.{{ $student->id }}.{{ $item->assessment_factor_id }}
                                            "
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            class="w-28 rounded-lg
                                                   border
                                                   border-zinc-300
                                                   px-3 py-2
                                                   dark:border-zinc-700
                                                   dark:bg-zinc-800"
                                        >

                                        @error(
                                            "scores.{$student->id}.{$item->assessment_factor_id}"
                                        )

                                            <p class="mt-1 text-xs text-red-500">
                                                {{ $message }}
                                            </p>

                                        @enderror

                                    @endif

                                </td>

                            @endforeach


                            <td class="p-3">

                                @if ($final)

                                    <span class="font-semibold">
                                        {{ number_format(
                                            $final->final_score,
                                            2
                                        ) }}
                                    </span>

                                @else

                                    -

                                @endif

                            </td>


                            <td class="p-3">

                                @if ($final)

                                    <div class="font-semibold">
                                        {{ $final->letter_grade ?? '-' }}
                                    </div>

                                    <div class="text-xs text-zinc-500">
                                        {{ $final->description }}
                                    </div>

                                @else

                                    -

                                @endif

                            </td>


                            <td class="p-3">

                                <div class="flex flex-col gap-2">

                                    @can('assessments.scores.manage')

                                        <button
                                            wire:click="
                                                saveStudent(
                                                    {{ $student->id }}
                                                )
                                            "
                                            type="button"
                                            class="rounded-lg
                                                   border
                                                   border-zinc-300
                                                   px-3 py-1.5"
                                        >
                                            Simpan
                                        </button>

                                    @endcan


                                    @can('assessments.calculate')

                                        <button
                                            wire:click="
                                                calculateStudent(
                                                    {{ $student->id }}
                                                )
                                            "
                                            type="button"
                                            class="rounded-lg
                                                   bg-zinc-900
                                                   px-3 py-1.5
                                                   text-white
                                                   dark:bg-white
                                                   dark:text-zinc-900"
                                        >
                                            Hitung
                                        </button>

                                    @endcan

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="20"
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Tidak ada siswa.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    @endif

</div>
