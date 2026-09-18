<div class="mx-auto max-w-7xl space-y-6">

    {{-- =====================================================
    HEADER
    ====================================================== --}}

    <div>
        <div
            class="flex flex-col justify-between
                   gap-4 sm:flex-row sm:items-end"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight
                           text-zinc-900 dark:text-white"
                >
                    Nilai Saya
                </h1>

                <p
                    class="mt-1 text-sm text-zinc-500
                           dark:text-zinc-400"
                >
                    Lihat hasil penilaian kegiatan
                    Pramuka pada semester aktif.
                </p>
            </div>

            @if ($isOfficialSnapshot)
                <div
                    class="inline-flex items-center gap-2
                           self-start rounded-full
                           bg-emerald-50 px-3 py-1.5
                           text-xs font-semibold
                           text-emerald-700
                           dark:bg-emerald-950/50
                           dark:text-emerald-300"
                >
                    <flux:icon.check-badge
                        class="size-4"
                    />

                    Nilai Resmi

                    @if ($selectedClosure?->version)
                        · v{{ $selectedClosure->version }}
                    @endif
                </div>
            @elseif ($selectedConfig)
                <div
                    class="inline-flex items-center gap-2
                           self-start rounded-full
                           bg-amber-50 px-3 py-1.5
                           text-xs font-semibold
                           text-amber-700
                           dark:bg-amber-950/50
                           dark:text-amber-300"
                >
                    <flux:icon.clock
                        class="size-4"
                    />

                    Semester Berjalan
                </div>
            @endif
        </div>
    </div>


    {{-- =====================================================
    SISWA BELUM TERHUBUNG
    ====================================================== --}}

    @if (! $student)

        <div
            class="rounded-2xl border
                   border-amber-200
                   bg-amber-50 p-6
                   dark:border-amber-900/50
                   dark:bg-amber-950/30"
        >
            <div class="flex gap-4">

                <div
                    class="flex size-11 shrink-0
                           items-center justify-center
                           rounded-xl
                           bg-amber-100 text-amber-700
                           dark:bg-amber-900/50
                           dark:text-amber-300"
                >
                    <flux:icon.exclamation-triangle
                        class="size-5"
                    />
                </div>

                <div>
                    <div
                        class="font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        Data siswa belum terhubung
                    </div>

                    <p
                        class="mt-1 text-sm
                               text-zinc-600
                               dark:text-zinc-400"
                    >
                        Akun ini belum terhubung dengan
                        data siswa. Hubungi administrator
                        sekolah.
                    </p>
                </div>

            </div>
        </div>


    @elseif (! $academicYear || ! $semester)

        {{-- =================================================
        TAHUN / SEMESTER BELUM AKTIF
        ================================================== --}}

        <div
            class="rounded-2xl border
                   border-zinc-200
                   bg-white px-6 py-12
                   text-center
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="mx-auto flex size-12
                       items-center justify-center
                       rounded-full bg-zinc-100
                       text-zinc-400
                       dark:bg-zinc-800"
            >
                <flux:icon.calendar-days
                    class="size-5"
                />
            </div>

            <h2
                class="mt-4 font-semibold
                       text-zinc-900
                       dark:text-white"
            >
                Semester aktif belum tersedia
            </h2>

            <p
                class="mx-auto mt-1 max-w-md
                       text-sm text-zinc-500"
            >
                Nilai akan tersedia setelah sekolah
                mengaktifkan tahun ajaran dan semester.
            </p>

        </div>


    @elseif (! $selectedConfig)

        {{-- =================================================
        KONFIGURASI NILAI BELUM ADA
        ================================================== --}}

        <div
            class="rounded-2xl border
                   border-zinc-200
                   bg-white px-6 py-12
                   text-center
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="mx-auto flex size-12
                       items-center justify-center
                       rounded-full bg-zinc-100
                       text-zinc-400
                       dark:bg-zinc-800"
            >
                <flux:icon.academic-cap
                    class="size-5"
                />
            </div>

            <h2
                class="mt-4 font-semibold
                       text-zinc-900
                       dark:text-white"
            >
                Nilai belum tersedia
            </h2>

            <p
                class="mx-auto mt-1 max-w-md
                       text-sm text-zinc-500"
            >
                Konfigurasi penilaian semester ini
                belum tersedia atau belum diaktifkan.
            </p>

        </div>


    @else

        {{-- =================================================
        IDENTITAS
        ================================================== --}}

        <section
            class="rounded-2xl border
                   border-zinc-200
                   bg-white p-5
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="grid gap-5
                       sm:grid-cols-2
                       lg:grid-cols-4"
            >

                <div>
                    <div
                        class="text-xs font-medium
                               uppercase tracking-wide
                               text-zinc-400"
                    >
                        Siswa
                    </div>

                    <div
                        class="mt-1 font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        {{ $student->name }}
                    </div>

                    <div
                        class="mt-0.5 text-sm
                               text-zinc-500"
                    >
                        NIS {{ $student->nis ?: '-' }}
                    </div>
                </div>


                <div>
                    <div
                        class="text-xs font-medium
                               uppercase tracking-wide
                               text-zinc-400"
                    >
                        Kelas
                    </div>

                    <div
                        class="mt-1 font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        {{
                            $enrollment
                                ?->classroom
                                ?->name
                            ?? '-'
                        }}
                    </div>
                </div>


                <div>
                    <div
                        class="text-xs font-medium
                               uppercase tracking-wide
                               text-zinc-400"
                    >
                        Tahun Ajaran
                    </div>

                    <div
                        class="mt-1 font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        {{ $academicYear->name }}
                    </div>
                </div>


                <div>
                    <div
                        class="text-xs font-medium
                               uppercase tracking-wide
                               text-zinc-400"
                    >
                        Semester
                    </div>

                    <div
                        class="mt-1 font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        {{ $semester->name }}
                    </div>
                </div>

            </div>

        </section>


        {{-- =================================================
        NILAI AKHIR
        ================================================== --}}

        <section
            class="overflow-hidden rounded-2xl
                   border border-zinc-200
                   bg-white
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="bg-gradient-to-br
                       from-emerald-700
                       via-emerald-600
                       to-teal-600
                       p-6 text-white"
            >

                <div
                    class="grid gap-6
                           md:grid-cols-[1fr_auto]
                           md:items-center"
                >

                    <div>

                        <p
                            class="text-sm font-medium
                                   text-emerald-100"
                        >
                            Nilai Akhir
                        </p>

                        @if (
                            $finalGrade
                            &&
                            $finalGrade->final_score
                            !== null
                        )

                            <div
                                class="mt-1 flex
                                       items-end gap-3"
                            >
                                <div
                                    class="text-5xl
                                           font-bold"
                                >
                                    {{
                                        number_format(
                                            (float)
                                            $finalGrade
                                                ->final_score,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </div>

                                @if (
                                    filled(
                                        $finalGrade
                                            ->letter_grade
                                    )
                                )
                                    <span
                                        class="mb-1
                                               rounded-lg
                                               bg-white/15
                                               px-3 py-1
                                               text-lg
                                               font-semibold"
                                    >
                                        {{
                                            $finalGrade
                                                ->letter_grade
                                        }}
                                    </span>
                                @endif
                            </div>

                        @else

                            <div
                                class="mt-2 text-2xl
                                       font-semibold"
                            >
                                Belum dihitung
                            </div>

                        @endif

                    </div>


                    <div
                        class="flex size-16
                               items-center
                               justify-center
                               rounded-2xl
                               bg-white/15"
                    >
                        <flux:icon.academic-cap
                            class="size-8"
                        />
                    </div>

                </div>

            </div>


            @if (
                $finalGrade
                &&
                filled(
                    $finalGrade->description
                )
            )

                <div class="p-6">

                    <div
                        class="text-xs font-semibold
                               uppercase tracking-wide
                               text-zinc-400"
                    >
                        Deskripsi
                    </div>

                    <p
                        class="mt-2 leading-7
                               text-zinc-700
                               dark:text-zinc-300"
                    >
                        {{ $finalGrade->description }}
                    </p>

                </div>

            @endif

        </section>


        {{-- =================================================
        NILAI PER FAKTOR
        ================================================== --}}

        <section>

            <div class="mb-3">

                <h2
                    class="text-lg font-semibold
                           text-zinc-900
                           dark:text-white"
                >
                    Komponen Penilaian
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Rincian nilai berdasarkan faktor
                    yang digunakan pada semester ini.
                </p>

            </div>


            <div
                class="grid gap-4
                       md:grid-cols-2
                       xl:grid-cols-3"
            >

                @forelse (
                    $selectedConfig->items
                    as $item
                )

                    @php
                        $score = $scores->get(
                            $item
                                ->assessment_factor_id
                        );
                    @endphp

                    <article
                        class="rounded-2xl border
                               border-zinc-200
                               bg-white p-5
                               dark:border-zinc-800
                               dark:bg-zinc-900"
                    >

                        <div
                            class="flex items-start
                                   justify-between
                                   gap-4"
                        >

                            <div>

                                <div
                                    class="font-semibold
                                           text-zinc-900
                                           dark:text-white"
                                >
                                    {{
                                        $item
                                            ->factor
                                            ->name
                                    }}
                                </div>

                                <div
                                    class="mt-1 text-xs
                                           text-zinc-500"
                                >
                                    Bobot
                                    {{
                                        number_format(
                                            (float)
                                            $item->weight,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    }}%
                                </div>

                            </div>


                            <div
                                class="flex size-12
                                       shrink-0
                                       items-center
                                       justify-center
                                       rounded-xl
                                       bg-emerald-50
                                       text-lg
                                       font-bold
                                       text-emerald-700
                                       dark:bg-emerald-950/50
                                       dark:text-emerald-300"
                            >
                                @if (
                                    $score
                                    &&
                                    $score->score
                                    !== null
                                )

                                    {{
                                        number_format(
                                            (float)
                                            $score->score,
                                            0
                                        )
                                    }}

                                @else
                                    -
                                @endif
                            </div>

                        </div>


                        <div
                            class="mt-5 h-2
                                   overflow-hidden
                                   rounded-full
                                   bg-zinc-100
                                   dark:bg-zinc-800"
                        >

                            @php
                                $percentage =
                                    $score
                                    &&
                                    $score->score
                                    !== null
                                        ? min(
                                            100,
                                            max(
                                                0,
                                                (float)
                                                $score
                                                    ->score
                                            )
                                        )
                                        : 0;
                            @endphp

                            <div
                                class="h-full
                                       rounded-full
                                       bg-emerald-600"
                                style="width:
                                    {{ $percentage }}%"
                            ></div>

                        </div>

                    </article>

                @empty

                    <div
                        class="col-span-full
                               rounded-2xl border
                               border-zinc-200
                               bg-white px-6 py-10
                               text-center
                               text-sm
                               text-zinc-500
                               dark:border-zinc-800
                               dark:bg-zinc-900"
                    >
                        Belum ada komponen penilaian.
                    </div>

                @endforelse

            </div>

        </section>


        {{-- =================================================
        STATUS DATA
        ================================================== --}}

        <section
            class="rounded-2xl border
                   border-zinc-200
                   bg-zinc-50 p-5
                   dark:border-zinc-800
                   dark:bg-zinc-900/50"
        >

            <div
                class="flex items-start gap-3"
            >

                @if ($isOfficialSnapshot)

                    <flux:icon.shield-check
                        class="mt-0.5 size-5
                               shrink-0
                               text-emerald-600"
                    />

                    <div>
                        <div
                            class="text-sm font-semibold
                                   text-zinc-900
                                   dark:text-white"
                        >
                            Nilai telah dikunci
                        </div>

                        <p
                            class="mt-1 text-sm
                                   leading-6
                                   text-zinc-500"
                        >
                            Data yang ditampilkan berasal
                            dari snapshot resmi semester dan
                            tidak mengikuti perubahan data
                            penilaian berjalan.
                        </p>
                    </div>

                @else

                    <flux:icon.information-circle
                        class="mt-0.5 size-5
                               shrink-0
                               text-amber-600"
                    />

                    <div>
                        <div
                            class="text-sm font-semibold
                                   text-zinc-900
                                   dark:text-white"
                        >
                            Nilai semester berjalan
                        </div>

                        <p
                            class="mt-1 text-sm
                                   leading-6
                                   text-zinc-500"
                        >
                            Nilai masih dapat berubah selama
                            semester belum dikunci oleh
                            sekolah.
                        </p>
                    </div>

                @endif

            </div>

        </section>

    @endif

</div>