<div class="space-y-6">

    {{-- =========================================================
    HEADER
    ========================================================== --}}

    <div
        class="flex flex-col gap-4
               lg:flex-row
               lg:items-start
               lg:justify-between"
    >

        <div>

            <h1
                class="text-2xl
                       font-semibold"
            >
                Sinkronisasi Penilaian
            </h1>


            <p
                class="mt-1 max-w-3xl
                       text-sm leading-6
                       text-zinc-500"
            >
                Periksa dan sinkronkan nilai kehadiran,
                nilai faktor, dan nilai akhir setelah
                terjadi perubahan pada data penilaian.
            </p>

        </div>


        @can('assessment_sync.manage')

            <button
                type="button"
                wire:click="synchronizeAll"
                wire:confirm="Sinkronkan seluruh konfigurasi penilaian yang belum terbaru pada periode ini?"
                wire:loading.attr="disabled"
                wire:target="synchronizeAll"
                @disabled(
                    $staleConfigs === 0
                )
                class="inline-flex
                       items-center
                       justify-center
                       rounded-lg
                       bg-zinc-900
                       px-5 py-2.5
                       text-sm font-medium
                       text-white
                       transition
                       hover:bg-zinc-800
                       disabled:cursor-not-allowed
                       disabled:opacity-50
                       dark:bg-white
                       dark:text-zinc-900
                       dark:hover:bg-zinc-200"
            >

                <span
                    wire:loading.remove
                    wire:target="synchronizeAll"
                >
                    Sinkronkan Semua
                </span>


                <span
                    wire:loading
                    wire:target="synchronizeAll"
                >
                    Menyinkronkan...
                </span>

            </button>

        @endcan

    </div>


    {{-- =========================================================
    FLASH
    ========================================================== --}}

    @if (session('status'))

        <div
            class="rounded-xl border
                   border-green-200
                   bg-green-50
                   px-4 py-3
                   text-sm
                   text-green-700
                   dark:border-green-900
                   dark:bg-green-950/40
                   dark:text-green-300"
        >
            {{ session('status') }}
        </div>

    @endif


    @error('sync')

        <div
            class="rounded-xl border
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


    {{-- =========================================================
    FILTER
    ========================================================== --}}

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-5
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

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
                    wire:model.live="academicYearId"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           text-sm
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

                    <option value="">
                        -- Pilih Tahun Ajaran --
                    </option>


                    @foreach (
                        $academicYears
                        as $academicYear
                    )

                        <option
                            value="{{ $academicYear->id }}"
                        >
                            {{ $academicYear->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- SEMESTER --}}

            <div>

                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Semester
                </label>


                <select
                    wire:model.live="semesterId"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           text-sm
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

                    <option value="">
                        -- Pilih Semester --
                    </option>


                    @foreach (
                        $semesters
                        as $semester
                    )

                        <option
                            value="{{ $semester->id }}"
                        >
                            {{ $semester->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- FILTER STALE --}}

            <div
                class="flex items-end"
            >

                <label
                    class="flex w-full
                           cursor-pointer
                           items-center gap-3
                           rounded-lg border
                           border-zinc-200
                           px-4 py-2.5
                           text-sm
                           dark:border-zinc-700"
                >

                    <input
                        type="checkbox"
                        wire:model.live="onlyStale"
                        class="rounded
                               border-zinc-300"
                    >

                    <span>
                        Hanya tampilkan yang
                        perlu disinkronkan
                    </span>

                </label>

            </div>

        </div>

    </section>


    {{-- =========================================================
    SUMMARY
    ========================================================== --}}

    <div
        class="grid gap-4
               sm:grid-cols-2
               xl:grid-cols-4"
    >

        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-5
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="text-xs font-medium
                       text-zinc-500"
            >
                Konfigurasi
            </div>


            <div
                class="mt-2 text-2xl
                       font-semibold"
            >
                {{ number_format(
                    $totalConfigs
                ) }}
            </div>

        </div>


        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-5
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="text-xs font-medium
                       text-zinc-500"
            >
                Perlu Sinkronisasi
            </div>


            <div
                class="mt-2 text-2xl
                       font-semibold"
            >
                {{ number_format(
                    $staleConfigs
                ) }}
            </div>

        </div>


        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-5
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="text-xs font-medium
                       text-zinc-500"
            >
                Nilai Kehadiran Stale
            </div>


            <div
                class="mt-2 text-2xl
                       font-semibold"
            >
                {{ number_format(
                    $staleAttendance
                ) }}
            </div>

        </div>


        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-5
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="text-xs font-medium
                       text-zinc-500"
            >
                Nilai Akhir Stale
            </div>


            <div
                class="mt-2 text-2xl
                       font-semibold"
            >
                {{ number_format(
                    $staleFinal
                ) }}
            </div>

        </div>

    </div>


    {{-- =========================================================
    CONFIGS
    ========================================================== --}}

    <div class="space-y-4">

        @forelse (
            $configs
            as $config
        )

            @php
                $sync =
                    $config->getAttribute(
                        'sync_status'
                    );

                $attendance =
                    $sync['attendance'];

                $final =
                    $sync['final'];

                $isStale =
                    $sync['is_stale'];
            @endphp


            <section
                wire:key="assessment-sync-{{ $config->id }}"
                class="rounded-xl border
                       bg-white
                       shadow-sm
                       dark:bg-zinc-900
                       {{
                            $isStale
                                ? 'border-amber-300 dark:border-amber-900'
                                : 'border-zinc-200 dark:border-zinc-800'
                       }}"
            >

                {{-- HEADER CARD --}}

                <div
                    class="flex flex-col gap-4
                           border-b
                           border-zinc-200
                           p-5
                           lg:flex-row
                           lg:items-center
                           lg:justify-between
                           dark:border-zinc-800"
                >

                    <div>

                        <div
                            class="flex flex-wrap
                                   items-center
                                   gap-2"
                        >

                            <h2
                                class="text-lg
                                       font-semibold"
                            >
                                Konfigurasi Penilaian
                                #{{ $config->id }}
                            </h2>


                            @if ($config->is_active)

                                <span
                                    class="rounded-full
                                           bg-green-100
                                           px-2.5 py-1
                                           text-xs font-medium
                                           text-green-700
                                           dark:bg-green-950
                                           dark:text-green-300"
                                >
                                    Aktif
                                </span>

                            @else

                                <span
                                    class="rounded-full
                                           bg-zinc-100
                                           px-2.5 py-1
                                           text-xs font-medium
                                           text-zinc-600
                                           dark:bg-zinc-800
                                           dark:text-zinc-300"
                                >
                                    Tidak Aktif
                                </span>

                            @endif


                            @if ($isStale)

                                <span
                                    class="rounded-full
                                           bg-amber-100
                                           px-2.5 py-1
                                           text-xs font-medium
                                           text-amber-700
                                           dark:bg-amber-950
                                           dark:text-amber-300"
                                >
                                    Perlu Sinkronisasi
                                </span>

                            @else

                                <span
                                    class="rounded-full
                                           bg-green-100
                                           px-2.5 py-1
                                           text-xs font-medium
                                           text-green-700
                                           dark:bg-green-950
                                           dark:text-green-300"
                                >
                                    Sinkron
                                </span>

                            @endif

                        </div>


                        <p
                            class="mt-2 text-sm
                                   text-zinc-500"
                        >
                            {{ $config->academicYear?->name ?? '-' }}

                            ·

                            {{ $config->semester?->name ?? '-' }}

                            ·

                            {{ $config->items->count() }}
                            faktor penilaian
                        </p>

                    </div>


                    @can('assessment_sync.manage')

                        <button
                            type="button"
                            wire:click="synchronize({{ $config->id }})"
                            wire:confirm="Hitung ulang data penilaian untuk konfigurasi ini?"
                            wire:loading.attr="disabled"
                            wire:target="synchronize({{ $config->id }})"
                            @disabled(
                                ! $config->is_active
                                ||
                                ! $isStale
                            )
                            class="inline-flex
                                   shrink-0
                                   items-center
                                   justify-center
                                   rounded-lg
                                   bg-amber-700
                                   px-4 py-2.5
                                   text-sm font-medium
                                   text-white
                                   transition
                                   hover:bg-amber-800
                                   disabled:cursor-not-allowed
                                   disabled:opacity-50"
                        >

                            <span
                                wire:loading.remove
                                wire:target="synchronize({{ $config->id }})"
                            >
                                Sinkronkan
                            </span>


                            <span
                                wire:loading
                                wire:target="synchronize({{ $config->id }})"
                            >
                                Memproses...
                            </span>

                        </button>

                    @endcan

                </div>


                {{-- CONTENT --}}

                <div class="p-5">

                    <div
                        class="grid gap-4
                               sm:grid-cols-2
                               xl:grid-cols-4"
                    >

                        {{-- ATTENDANCE --}}

                        <div
                            class="rounded-lg
                                   bg-zinc-50
                                   p-4
                                   dark:bg-zinc-950"
                        >

                            <div
                                class="text-xs
                                       text-zinc-500"
                            >
                                Nilai Kehadiran
                                Belum Sinkron
                            </div>


                            <div
                                class="mt-1 text-xl
                                       font-semibold"
                            >
                                {{ number_format(
                                    $attendance[
                                        'stale_count'
                                    ]
                                    ?? 0
                                ) }}
                            </div>

                        </div>



                        {{-- FINAL --}}

                        <div
                            class="rounded-lg
                                   bg-zinc-50
                                   p-4
                                   dark:bg-zinc-950"
                        >

                            <div
                                class="text-xs
                                       text-zinc-500"
                            >
                                Nilai Akhir
                                Belum Sinkron
                            </div>


                            <div
                                class="mt-1 text-xl
                                       font-semibold"
                            >
                                {{ number_format(
                                    $final[
                                        'stale_count'
                                    ]
                                    ?? 0
                                ) }}
                            </div>

                        </div>


                        {{-- MISSING FINAL --}}

                        <div
                            class="rounded-lg
                                   bg-zinc-50
                                   p-4
                                   dark:bg-zinc-950"
                        >

                            <div
                                class="text-xs
                                       text-zinc-500"
                            >
                                Belum Memiliki
                                Nilai Akhir
                            </div>


                            <div
                                class="mt-1 text-xl
                                       font-semibold"
                            >
                                {{ number_format(
                                    $final[
                                        'missing_final_count'
                                    ]
                                    ?? 0
                                ) }}
                            </div>

                        </div>


                        {{-- SCORE CHANGED --}}

                        <div
                            class="rounded-lg
                                   bg-zinc-50
                                   p-4
                                   dark:bg-zinc-950"
                        >

                            <div
                                class="text-xs
                                       text-zinc-500"
                            >
                                Faktor Nilai
                                Berubah
                            </div>


                            <div
                                class="mt-1 text-xl
                                       font-semibold"
                            >
                                {{ number_format(
                                    $final[
                                        'score_changed_count'
                                    ]
                                    ?? 0
                                ) }}
                            </div>

                        </div>

                        <div
                            class="rounded-lg
                                bg-zinc-50
                                p-4
                                dark:bg-zinc-950"
                        >

                            <div
                                class="text-xs
                                    text-zinc-500"
                            >
                                Konfigurasi Berubah
                            </div>


                            <div
                                class="mt-1 text-xl
                                    font-semibold"
                            >
                                {{ number_format(
                                    $final[
                                        'configuration_changed_count'
                                    ]
                                    ?? 0
                                ) }}
                            </div>

                        </div>

                    </div>


                    {{-- REASON --}}

                    @if (
                        count(
                            $sync['reasons']
                        ) > 0
                    )

                        <div
                            class="mt-5 rounded-lg
                                   border border-amber-200
                                   bg-amber-50
                                   p-4
                                   text-sm
                                   text-amber-800
                                   dark:border-amber-900
                                   dark:bg-amber-950/40
                                   dark:text-amber-300"
                        >

                            <div class="font-medium">
                                Penyebab
                            </div>


                            <ul
                                class="mt-2 list-disc
                                       space-y-1
                                       pl-5"
                            >

                                @foreach (
                                    $sync['reasons']
                                    as $reason
                                )

                                    <li>
                                        {{ $reason }}
                                    </li>

                                @endforeach

                            </ul>

                        </div>

                    @elseif (! $isStale)

                        <div
                            class="mt-5 rounded-lg
                                   border border-green-200
                                   bg-green-50
                                   px-4 py-3
                                   text-sm
                                   text-green-700
                                   dark:border-green-900
                                   dark:bg-green-950/40
                                   dark:text-green-300"
                        >
                            Seluruh nilai pada konfigurasi
                            ini sudah sinkron.
                        </div>

                    @endif

                </div>

            </section>

        @empty

            <div
                class="rounded-xl border
                       border-zinc-200
                       bg-white p-10
                       text-center
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >

                <div class="font-medium">
                    Belum ada konfigurasi penilaian
                </div>


                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Pilih tahun ajaran dan semester
                    yang memiliki konfigurasi penilaian.
                </p>

            </div>

        @endforelse

    </div>

</div>