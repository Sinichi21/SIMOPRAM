<div class="space-y-6">

    {{-- =========================================================
    HEADER
    ========================================================== --}}

    <div>
        <h1 class="text-2xl font-semibold">
            Pengaturan Bobot Kehadiran
        </h1>

        <p
            class="mt-1 max-w-3xl
                   text-sm leading-6
                   text-zinc-500"
        >
            Atur kontribusi setiap status absensi
            terhadap faktor penilaian kehadiran.
            Konfigurasi disimpan secara terpisah
            untuk setiap sekolah.
        </p>
    </div>


    {{-- =========================================================
    FLASH STATUS
    ========================================================== --}}

    @if (session('status'))

        <div
            class="rounded-lg border
                   border-green-200
                   bg-green-50
                   px-4 py-3
                   text-sm
                   text-green-700
                   dark:border-green-900
                   dark:bg-green-950
                   dark:text-green-300"
        >
            {{ session('status') }}
        </div>

    @endif


    {{-- =========================================================
    PENJELASAN
    ========================================================== --}}

    <div
        class="rounded-xl border
               border-zinc-200
               bg-zinc-50
               p-5
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <h2 class="font-semibold">
            Cara Perhitungan
        </h2>

        <p
            class="mt-2 text-sm
                   leading-6
                   text-zinc-500"
        >
            Persentase di bawah bukan merupakan
            bobot faktor penilaian utama.
            Nilai ini menentukan berapa persen
            nilai kehadiran yang diperoleh siswa
            untuk setiap status absensi.
        </p>


        <div
            class="mt-4 rounded-lg
                   border border-zinc-200
                   bg-white p-4
                   text-sm
                   dark:border-zinc-700
                   dark:bg-zinc-950"
        >
            <div class="font-medium">
                Contoh konfigurasi
            </div>

            <div
                class="mt-1 text-zinc-500"
            >
                Hadir 100%, Terlambat 75%,
                Sakit 75%, Izin 75%,
                dan Alpa 0%.
            </div>
        </div>

    </div>


    {{-- =========================================================
    FORM
    ========================================================== --}}

    <form
        wire:submit="save"
        class="space-y-6"
    >

        {{-- =====================================================
        BOBOT STATUS KEHADIRAN
        ====================================================== --}}

        <section
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="flex flex-col gap-3
                       sm:flex-row
                       sm:items-center
                       sm:justify-between"
            >

                <div>
                    <h2
                        class="text-lg font-semibold"
                    >
                        Bobot Status Kehadiran
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500"
                    >
                        Konfigurasi yang digunakan
                        untuk menghitung nilai kehadiran
                        otomatis siswa.
                    </p>
                </div>


                {{-- VERSION BADGE --}}

                <div
                    class="inline-flex w-fit
                           items-center
                           rounded-full
                           bg-zinc-100
                           px-3 py-1
                           text-xs font-medium
                           text-zinc-700
                           dark:bg-zinc-800
                           dark:text-zinc-300"
                >
                    Versi {{ $currentVersion }}
                </div>

            </div>


            <div
                class="mt-6 grid gap-5
                       sm:grid-cols-2
                       lg:grid-cols-3
                       xl:grid-cols-5"
            >

                {{-- =================================================
                HADIR
                ================================================== --}}

                <div>

                    <label
                        for="presentWeight"
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Hadir
                    </label>


                    <div class="relative">

                        <input
                            id="presentWeight"
                            type="number"
                            wire:model.live.debounce.300ms="presentWeight"
                            min="0"
                            max="100"
                            step="0.01"
                            inputmode="decimal"
                            class="w-full rounded-lg
                                   border border-zinc-300
                                   bg-white
                                   px-3 py-2 pr-10
                                   text-sm
                                   outline-none
                                   transition
                                   focus:border-zinc-500
                                   focus:ring-2
                                   focus:ring-zinc-200
                                   dark:border-zinc-700
                                   dark:bg-zinc-950
                                   dark:focus:border-zinc-500
                                   dark:focus:ring-zinc-800"
                        >


                        <span
                            class="pointer-events-none
                                   absolute right-3
                                   top-1/2
                                   -translate-y-1/2
                                   text-sm
                                   text-zinc-500"
                        >
                            %
                        </span>

                    </div>


                    @error('presentWeight')

                        <div
                            class="mt-1 text-sm
                                   text-red-600
                                   dark:text-red-400"
                        >
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- =================================================
                TERLAMBAT
                ================================================== --}}

                <div>

                    <label
                        for="lateWeight"
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Terlambat
                    </label>


                    <div class="relative">

                        <input
                            id="lateWeight"
                            type="number"
                            wire:model.live.debounce.300ms="lateWeight"
                            min="0"
                            max="100"
                            step="0.01"
                            inputmode="decimal"
                            class="w-full rounded-lg
                                   border border-zinc-300
                                   bg-white
                                   px-3 py-2 pr-10
                                   text-sm
                                   outline-none
                                   transition
                                   focus:border-zinc-500
                                   focus:ring-2
                                   focus:ring-zinc-200
                                   dark:border-zinc-700
                                   dark:bg-zinc-950
                                   dark:focus:border-zinc-500
                                   dark:focus:ring-zinc-800"
                        >


                        <span
                            class="pointer-events-none
                                   absolute right-3
                                   top-1/2
                                   -translate-y-1/2
                                   text-sm
                                   text-zinc-500"
                        >
                            %
                        </span>

                    </div>


                    @error('lateWeight')

                        <div
                            class="mt-1 text-sm
                                   text-red-600
                                   dark:text-red-400"
                        >
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- =================================================
                SAKIT
                ================================================== --}}

                <div>

                    <label
                        for="sickWeight"
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Sakit
                    </label>


                    <div class="relative">

                        <input
                            id="sickWeight"
                            type="number"
                            wire:model.live.debounce.300ms="sickWeight"
                            min="0"
                            max="100"
                            step="0.01"
                            inputmode="decimal"
                            class="w-full rounded-lg
                                   border border-zinc-300
                                   bg-white
                                   px-3 py-2 pr-10
                                   text-sm
                                   outline-none
                                   transition
                                   focus:border-zinc-500
                                   focus:ring-2
                                   focus:ring-zinc-200
                                   dark:border-zinc-700
                                   dark:bg-zinc-950
                                   dark:focus:border-zinc-500
                                   dark:focus:ring-zinc-800"
                        >


                        <span
                            class="pointer-events-none
                                   absolute right-3
                                   top-1/2
                                   -translate-y-1/2
                                   text-sm
                                   text-zinc-500"
                        >
                            %
                        </span>

                    </div>


                    @error('sickWeight')

                        <div
                            class="mt-1 text-sm
                                   text-red-600
                                   dark:text-red-400"
                        >
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- =================================================
                IZIN
                ================================================== --}}

                <div>

                    <label
                        for="excusedWeight"
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Izin
                    </label>


                    <div class="relative">

                        <input
                            id="excusedWeight"
                            type="number"
                            wire:model.live.debounce.300ms="excusedWeight"
                            min="0"
                            max="100"
                            step="0.01"
                            inputmode="decimal"
                            class="w-full rounded-lg
                                   border border-zinc-300
                                   bg-white
                                   px-3 py-2 pr-10
                                   text-sm
                                   outline-none
                                   transition
                                   focus:border-zinc-500
                                   focus:ring-2
                                   focus:ring-zinc-200
                                   dark:border-zinc-700
                                   dark:bg-zinc-950
                                   dark:focus:border-zinc-500
                                   dark:focus:ring-zinc-800"
                        >


                        <span
                            class="pointer-events-none
                                   absolute right-3
                                   top-1/2
                                   -translate-y-1/2
                                   text-sm
                                   text-zinc-500"
                        >
                            %
                        </span>

                    </div>


                    @error('excusedWeight')

                        <div
                            class="mt-1 text-sm
                                   text-red-600
                                   dark:text-red-400"
                        >
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- =================================================
                ALPA
                ================================================== --}}

                <div>

                    <label
                        for="absentWeight"
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Alpa
                    </label>


                    <div class="relative">

                        <input
                            id="absentWeight"
                            type="number"
                            wire:model.live.debounce.300ms="absentWeight"
                            min="0"
                            max="100"
                            step="0.01"
                            inputmode="decimal"
                            class="w-full rounded-lg
                                   border border-zinc-300
                                   bg-white
                                   px-3 py-2 pr-10
                                   text-sm
                                   outline-none
                                   transition
                                   focus:border-zinc-500
                                   focus:ring-2
                                   focus:ring-zinc-200
                                   dark:border-zinc-700
                                   dark:bg-zinc-950
                                   dark:focus:border-zinc-500
                                   dark:focus:ring-zinc-800"
                        >


                        <span
                            class="pointer-events-none
                                   absolute right-3
                                   top-1/2
                                   -translate-y-1/2
                                   text-sm
                                   text-zinc-500"
                        >
                            %
                        </span>

                    </div>


                    @error('absentWeight')

                        <div
                            class="mt-1 text-sm
                                   text-red-600
                                   dark:text-red-400"
                        >
                            {{ $message }}
                        </div>

                    @enderror

                </div>

            </div>

        </section>


        {{-- =====================================================
        PRATINJAU
        ====================================================== --}}

        <section
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="text-lg font-semibold">
                Pratinjau Nilai Kehadiran
            </h2>


            <p
                class="mt-1 text-sm
                       text-zinc-500"
            >
                Nilai berikut menunjukkan skor
                yang diperoleh siswa untuk satu
                pertemuan apabila nilai maksimum
                kehadiran adalah 100.
            </p>


            @php
                $preview = [
                    [
                        'label' => 'Hadir',
                        'value' => $presentWeight,
                    ],
                    [
                        'label' => 'Terlambat',
                        'value' => $lateWeight,
                    ],
                    [
                        'label' => 'Sakit',
                        'value' => $sickWeight,
                    ],
                    [
                        'label' => 'Izin',
                        'value' => $excusedWeight,
                    ],
                    [
                        'label' => 'Alpa',
                        'value' => $absentWeight,
                    ],
                ];
            @endphp


            <div
                class="mt-5 grid gap-3
                       sm:grid-cols-2
                       lg:grid-cols-3
                       xl:grid-cols-5"
            >

                @foreach ($preview as $item)

                    <div
                        wire:key="attendance-preview-{{ $item['label'] }}"
                        class="rounded-lg border
                               border-zinc-200
                               bg-zinc-50
                               p-4 text-center
                               dark:border-zinc-700
                               dark:bg-zinc-950"
                    >

                        <div
                            class="text-2xl
                                   font-semibold"
                        >
                            {{ number_format(
                                (float) $item['value'],
                                2
                            ) }}
                        </div>


                        <div
                            class="mt-1 text-xs
                                   font-medium
                                   text-zinc-500"
                        >
                            {{ $item['label'] }}
                        </div>

                    </div>

                @endforeach

            </div>

        </section>


        {{-- =====================================================
        STATUS SINKRONISASI
        ====================================================== --}}

        @if (
            $hasStaleScores
            ||
            $hasStaleFinalGrades
        )

            <section
                class="rounded-xl border
                       border-amber-300
                       bg-amber-50
                       p-5
                       dark:border-amber-900
                       dark:bg-amber-950/40"
            >

                <div
                    class="flex flex-col gap-5
                           lg:flex-row
                           lg:items-center
                           lg:justify-between"
                >

                    <div class="min-w-0">

                        <div
                            class="font-semibold
                                   text-amber-800
                                   dark:text-amber-300"
                        >
                            Data Penilaian Perlu Disinkronkan
                        </div>


                        <p
                            class="mt-1 text-sm
                                   leading-6
                                   text-amber-700
                                   dark:text-amber-400"
                        >
                            Terdapat data nilai yang
                            belum menggunakan konfigurasi
                            bobot kehadiran versi

                            <strong>
                                {{ $currentVersion }}
                            </strong>.

                            Sinkronkan nilai sebelum
                            melakukan pencetakan laporan
                            resmi.
                        </p>


                        <div
                            class="mt-4 grid gap-3
                                   sm:grid-cols-2"
                        >

                            {{-- NILAI KEHADIRAN STALE --}}

                            <div
                                class="rounded-lg
                                       border
                                       border-amber-200
                                       bg-white/70
                                       p-4
                                       dark:border-amber-900
                                       dark:bg-zinc-950/40"
                            >

                                <div
                                    class="text-xs
                                           font-medium
                                           text-zinc-500"
                                >
                                    Nilai Kehadiran
                                    Belum Sinkron
                                </div>


                                <div
                                    class="mt-1 text-2xl
                                           font-semibold
                                           text-zinc-900
                                           dark:text-zinc-100"
                                >
                                    {{ number_format(
                                        $staleScoreCount
                                    ) }}
                                </div>

                            </div>


                            {{-- FINAL GRADE STALE --}}

                            <div
                                class="rounded-lg
                                       border
                                       border-amber-200
                                       bg-white/70
                                       p-4
                                       dark:border-amber-900
                                       dark:bg-zinc-950/40"
                            >

                                <div
                                    class="text-xs
                                           font-medium
                                           text-zinc-500"
                                >
                                    Nilai Akhir
                                    Belum Sinkron
                                </div>


                                <div
                                    class="mt-1 text-2xl
                                           font-semibold
                                           text-zinc-900
                                           dark:text-zinc-100"
                                >
                                    {{ number_format(
                                        $staleFinalGradeCount
                                    ) }}
                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- SINKRONISASI --}}

                    @can('assessments.calculate')

                        <button
                            type="button"
                            wire:click="synchronizeScores"
                            wire:confirm="Hitung ulang nilai kehadiran dan nilai akhir menggunakan konfigurasi terbaru?"
                            wire:loading.attr="disabled"
                            wire:target="synchronizeScores"
                            class="inline-flex shrink-0
                                   items-center
                                   justify-center
                                   rounded-lg
                                   bg-amber-700
                                   px-5 py-2.5
                                   text-sm font-medium
                                   text-white
                                   transition
                                   hover:bg-amber-800
                                   disabled:cursor-not-allowed
                                   disabled:opacity-50"
                        >

                            <span
                                wire:loading.remove
                                wire:target="synchronizeScores"
                            >
                                Sinkronkan Semua Nilai
                            </span>


                            <span
                                wire:loading
                                wire:target="synchronizeScores"
                            >
                                Menyinkronkan...
                            </span>

                        </button>

                    @endcan

                </div>

            </section>

        @else

            {{-- =================================================
            STATUS SUDAH SINKRON
            ================================================== --}}

            <section
                class="rounded-xl border
                       border-green-200
                       bg-green-50
                       p-4
                       text-green-700
                       dark:border-green-900
                       dark:bg-green-950/40
                       dark:text-green-300"
            >

                <div class="font-medium">
                    Data penilaian sudah sinkron
                </div>


                <p
                    class="mt-1 text-sm
                           leading-6"
                >
                    Nilai kehadiran dan nilai akhir
                    sudah menggunakan konfigurasi
                    bobot kehadiran versi

                    <strong>
                        {{ $currentVersion }}
                    </strong>.
                </p>

            </section>

        @endif


        {{-- =====================================================
        ACTION BUTTONS
        ====================================================== --}}

        @can('attendance_score_settings.manage')

            <div
                class="flex flex-col gap-3
                       border-t
                       border-zinc-200
                       pt-6
                       sm:flex-row
                       sm:items-center
                       sm:justify-end
                       dark:border-zinc-800"
            >

                {{-- RESET DEFAULT --}}

                <button
                    type="button"
                    wire:click="resetDefaults"
                    wire:loading.attr="disabled"
                    wire:target="resetDefaults"
                    class="inline-flex
                           items-center
                           justify-center
                           rounded-lg border
                           border-zinc-300
                           bg-white
                           px-4 py-2.5
                           text-sm font-medium
                           text-zinc-700
                           transition
                           hover:bg-zinc-50
                           disabled:cursor-not-allowed
                           disabled:opacity-50
                           dark:border-zinc-700
                           dark:bg-zinc-900
                           dark:text-zinc-200
                           dark:hover:bg-zinc-800"
                >

                    <span
                        wire:loading.remove
                        wire:target="resetDefaults"
                    >
                        Gunakan Nilai Default
                    </span>


                    <span
                        wire:loading
                        wire:target="resetDefaults"
                    >
                        Memuat...
                    </span>

                </button>


                {{-- SAVE --}}

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
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
                        wire:target="save"
                    >
                        Simpan Pengaturan
                    </span>


                    <span
                        wire:loading
                        wire:target="save"
                    >
                        Menyimpan...
                    </span>

                </button>

            </div>

        @endcan

    </form>

</div>