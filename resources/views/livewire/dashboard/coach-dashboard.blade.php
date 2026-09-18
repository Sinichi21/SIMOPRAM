<div
    class="mx-auto max-w-7xl space-y-5
           sm:space-y-6"
>

    {{-- =====================================================
    BELUM MEMILIH SEKOLAH
    ====================================================== --}}

    @if (! $hasSchool)

        <section
            class="overflow-hidden rounded-2xl
                   border border-amber-200
                   bg-white
                   dark:border-amber-900/50
                   dark:bg-zinc-900"
        >
            <div class="p-6 sm:p-8">

                <div
                    class="flex flex-col gap-5
                           sm:flex-row sm:items-center"
                >

                    <div
                        class="flex size-14 shrink-0
                               items-center justify-center
                               rounded-2xl
                               bg-amber-50
                               text-amber-600
                               dark:bg-amber-950/50
                               dark:text-amber-400"
                    >
                        <flux:icon.building-library
                            class="size-7"
                        />
                    </div>

                    <div class="min-w-0 flex-1">

                        <h1
                            class="text-xl font-bold
                                   text-zinc-900
                                   dark:text-white"
                        >
                            Pilih sekolah aktif
                        </h1>

                        <p
                            class="mt-1 max-w-2xl
                                   text-sm leading-6
                                   text-zinc-500
                                   dark:text-zinc-400"
                        >
                            Pilih sekolah yang sedang
                            Anda kelola melalui selector
                            sekolah pada sidebar.
                            Dashboard akan otomatis
                            menampilkan data sekolah
                            tersebut.
                        </p>

                    </div>

                </div>

            </div>
        </section>

    @else

        {{-- =================================================
        HERO
        ================================================== --}}

        <section
            class="relative overflow-hidden
                   rounded-2xl
                   bg-gradient-to-br
                   from-emerald-800
                   via-emerald-700
                   to-teal-700
                   text-white shadow-sm"
        >

            {{-- Decoration --}}
            <div
                class="pointer-events-none
                       absolute -right-16 -top-20
                       size-64 rounded-full
                       bg-white/10"
            ></div>

            <div
                class="pointer-events-none
                       absolute -bottom-24 right-24
                       size-48 rounded-full
                       bg-white/5"
            ></div>


            <div
                class="relative px-5 py-6
                       sm:px-7 sm:py-8"
            >

                <div
                    class="flex flex-col
                           justify-between gap-6
                           lg:flex-row
                           lg:items-center"
                >

                    <div class="min-w-0">

                        <div
                            class="inline-flex items-center
                                   gap-2 rounded-full
                                   bg-white/10
                                   px-3 py-1
                                   text-xs font-medium
                                   text-emerald-50
                                   ring-1 ring-white/15"
                        >
                            <span
                                class="size-2 rounded-full
                                       bg-emerald-300"
                            ></span>

                            Area Pembina
                        </div>


                        <h1
                            class="mt-4 text-2xl
                                   font-bold tracking-tight
                                   sm:text-3xl"
                        >
                            Halo,
                            {{ auth()->user()->name }}
                        </h1>


                        <p
                            class="mt-2 max-w-2xl
                                   text-sm leading-6
                                   text-emerald-50/90
                                   sm:text-base"
                        >
                            Pantau kegiatan, absensi,
                            jurnal, dan penilaian Pramuka
                            dari satu tempat.
                        </p>


                        <div
                            class="mt-5 flex flex-wrap
                                   gap-2"
                        >

                            @if ($academicYear)

                                <span
                                    class="inline-flex
                                           items-center gap-1.5
                                           rounded-lg
                                           bg-black/10
                                           px-3 py-1.5
                                           text-xs
                                           text-emerald-50"
                                >
                                    <flux:icon.calendar
                                        class="size-3.5"
                                    />

                                    {{
                                        $academicYear
                                            ->name
                                    }}
                                </span>

                            @endif


                            @if ($semester)

                                <span
                                    class="inline-flex
                                           items-center gap-1.5
                                           rounded-lg
                                           bg-black/10
                                           px-3 py-1.5
                                           text-xs
                                           text-emerald-50"
                                >
                                    <flux:icon.academic-cap
                                        class="size-3.5"
                                    />

                                    {{
                                        $semester->name
                                    }}
                                </span>

                            @endif

                        </div>

                    </div>


                    <div
                        class="hidden size-24
                               shrink-0 items-center
                               justify-center
                               rounded-3xl
                               bg-white/10
                               ring-1 ring-white/15
                               lg:flex"
                    >
                        <flux:icon.user-group
                            class="size-12
                                   text-white/90"
                        />
                    </div>

                </div>

            </div>

        </section>


        {{-- =================================================
        MENU CEPAT
        ================================================== --}}

        <section>

            <div
                class="mb-3 flex
                       items-end justify-between"
            >

                <div>
                    <h2
                        class="font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        Akses Cepat
                    </h2>

                    <p
                        class="mt-0.5 text-xs
                               text-zinc-500
                               sm:text-sm"
                    >
                        Aktivitas yang paling sering
                        digunakan pembina.
                    </p>
                </div>

            </div>


            <div
                class="grid grid-cols-2
                       gap-3
                       sm:grid-cols-3
                       xl:grid-cols-6"
            >

                @can('activities.view')

                    <a
                        href="{{
                            route(
                                'activities.index'
                            )
                        }}"
                        wire:navigate
                        class="group rounded-2xl
                               border border-zinc-200
                               bg-white p-4
                               transition
                               hover:-translate-y-0.5
                               hover:border-emerald-200
                               hover:shadow-sm
                               dark:border-zinc-800
                               dark:bg-zinc-900
                               dark:hover:border-emerald-900"
                    >
                        <div
                            class="flex size-10
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-emerald-50
                                   text-emerald-600
                                   transition
                                   group-hover:bg-emerald-100
                                   dark:bg-emerald-950/50
                                   dark:text-emerald-400"
                        >
                            <flux:icon.calendar-days
                                class="size-5"
                            />
                        </div>

                        <div
                            class="mt-3 text-sm
                                   font-semibold
                                   text-zinc-900
                                   dark:text-white"
                        >
                            Kegiatan
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-zinc-500"
                        >
                            Agenda Pramuka
                        </div>
                    </a>

                @endcan


                @can('attendance_sessions.view')

                    <a
                        href="{{
                            route(
                                'attendances.index'
                            )
                        }}"
                        wire:navigate
                        class="group rounded-2xl
                               border border-zinc-200
                               bg-white p-4
                               transition
                               hover:-translate-y-0.5
                               hover:border-blue-200
                               hover:shadow-sm
                               dark:border-zinc-800
                               dark:bg-zinc-900"
                    >
                        <div
                            class="flex size-10
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-blue-50
                                   text-blue-600
                                   dark:bg-blue-950/50
                                   dark:text-blue-400"
                        >
                            <flux:icon.clipboard-document-check
                                class="size-5"
                            />
                        </div>

                        <div
                            class="mt-3 text-sm
                                   font-semibold"
                        >
                            Absensi
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-zinc-500"
                        >
                            Kelola kehadiran
                        </div>
                    </a>

                @endcan


                @can('journals.view')

                    <a
                        href="{{
                            route(
                                'journals.index'
                            )
                        }}"
                        wire:navigate
                        class="group rounded-2xl
                               border border-zinc-200
                               bg-white p-4
                               transition
                               hover:-translate-y-0.5
                               hover:shadow-sm
                               dark:border-zinc-800
                               dark:bg-zinc-900"
                    >
                        <div
                            class="flex size-10
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-amber-50
                                   text-amber-600
                                   dark:bg-amber-950/50
                                   dark:text-amber-400"
                        >
                            <flux:icon.document-text
                                class="size-5"
                            />
                        </div>

                        <div
                            class="mt-3 text-sm
                                   font-semibold"
                        >
                            Jurnal
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-zinc-500"
                        >
                            Catatan kegiatan
                        </div>
                    </a>

                @endcan


                @can('activity_assessments.view')

                    <a
                        href="{{
                            route(
                                'activity-assessments.index'
                            )
                        }}"
                        wire:navigate
                        class="group rounded-2xl
                               border border-zinc-200
                               bg-white p-4
                               transition
                               hover:-translate-y-0.5
                               hover:shadow-sm
                               dark:border-zinc-800
                               dark:bg-zinc-900"
                    >
                        <div
                            class="flex size-10
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-violet-50
                                   text-violet-600
                                   dark:bg-violet-950/50
                                   dark:text-violet-400"
                        >
                            <flux:icon.academic-cap
                                class="size-5"
                            />
                        </div>

                        <div
                            class="mt-3 text-sm
                                   font-semibold"
                        >
                            Penilaian
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-zinc-500"
                        >
                            Nilai kegiatan
                        </div>
                    </a>

                @endcan


                @can('students.view')

                    <a
                        href="{{
                            route(
                                'students.index'
                            )
                        }}"
                        wire:navigate
                        class="group rounded-2xl
                               border border-zinc-200
                               bg-white p-4
                               transition
                               hover:-translate-y-0.5
                               hover:shadow-sm
                               dark:border-zinc-800
                               dark:bg-zinc-900"
                    >
                        <div
                            class="flex size-10
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-cyan-50
                                   text-cyan-600
                                   dark:bg-cyan-950/50
                                   dark:text-cyan-400"
                        >
                            <flux:icon.users
                                class="size-5"
                            />
                        </div>

                        <div
                            class="mt-3 text-sm
                                   font-semibold"
                        >
                            Siswa
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-zinc-500"
                        >
                            Data binaan
                        </div>
                    </a>

                @endcan


                <a
                    href="{{
                        route(
                            'announcements.my'
                        )
                    }}"
                    wire:navigate
                    class="group rounded-2xl
                           border border-zinc-200
                           bg-white p-4
                           transition
                           hover:-translate-y-0.5
                           hover:shadow-sm
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >
                    <div
                        class="flex size-10
                               items-center
                               justify-center
                               rounded-xl
                               bg-rose-50
                               text-rose-600
                               dark:bg-rose-950/50
                               dark:text-rose-400"
                    >
                        <flux:icon.megaphone
                            class="size-5"
                        />
                    </div>

                    <div
                        class="mt-3 text-sm
                               font-semibold"
                    >
                        Informasi
                    </div>

                    <div
                        class="mt-1 text-xs
                               text-zinc-500"
                    >
                        Pengumuman
                    </div>
                </a>

            </div>

        </section>


        {{-- =================================================
        STATISTIK
        ================================================== --}}

        <section>

            <h2
                class="mb-3 font-semibold
                       text-zinc-900
                       dark:text-white"
            >
                Ringkasan
            </h2>

            <div
                class="grid grid-cols-2
                       gap-3
                       md:grid-cols-3
                       xl:grid-cols-6"
            >

                @php
                    $summaryCards = [
                        [
                            'value' => $statistics['students'],
                            'label' => 'Siswa Aktif',
                            'icon' => 'users',
                            'class' => 'bg-cyan-50 text-cyan-600 dark:bg-cyan-950/50 dark:text-cyan-400',
                        ],
                        [
                            'value' => $statistics['upcoming'],
                            'label' => 'Akan Datang',
                            'icon' => 'calendar-days',
                            'class' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400',
                        ],
                        [
                            'value' => $statistics['today'],
                            'label' => 'Hari Ini',
                            'icon' => 'clock',
                            'class' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400',
                        ],
                        [
                            'value' => $statistics['attendance'],
                            'label' => 'Absensi Aktif',
                            'icon' => 'clipboard-document-check',
                            'class' => 'bg-violet-50 text-violet-600 dark:bg-violet-950/50 dark:text-violet-400',
                        ],
                        [
                            'value' => $statistics['journals'],
                            'label' => 'Jurnal Pending',
                            'icon' => 'document-text',
                            'class' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400',
                        ],
                        [
                            'value' => $statistics['assessments'],
                            'label' => 'Nilai Draft',
                            'icon' => 'academic-cap',
                            'class' => 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400',
                        ],
                    ];
                @endphp


                @foreach (
                    $summaryCards
                    as $card
                )

                    <div
                        class="rounded-2xl
                               border border-zinc-200
                               bg-white p-4
                               sm:p-5
                               dark:border-zinc-800
                               dark:bg-zinc-900"
                    >

                        <div
                            class="flex size-9
                                   items-center
                                   justify-center
                                   rounded-xl
                                   {{ $card['class'] }}"
                        >
                            <flux:icon
                                :name="$card['icon']"
                                class="size-4.5"
                            />
                        </div>


                        <div
                            class="mt-4 text-2xl
                                   font-bold
                                   tracking-tight
                                   text-zinc-900
                                   dark:text-white"
                        >
                            {{ $card['value'] }}
                        </div>

                        <div
                            class="mt-1 text-xs
                                   leading-5
                                   text-zinc-500"
                        >
                            {{ $card['label'] }}
                        </div>

                    </div>

                @endforeach

            </div>

        </section>


        {{-- =================================================
        PERLU PERHATIAN
        ================================================== --}}

        @if (
            $statistics['attendance'] > 0
            ||
            $statistics['journals'] > 0
            ||
            $statistics['assessments'] > 0
        )

            <section
                class="rounded-2xl
                       border border-zinc-200
                       bg-white
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >

                <div
                    class="border-b
                           border-zinc-100
                           px-5 py-4
                           dark:border-zinc-800"
                >
                    <h2
                        class="font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        Perlu Perhatian
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500"
                    >
                        Beberapa pekerjaan masih
                        membutuhkan tindak lanjut.
                    </p>
                </div>


                <div
                    class="grid gap-3 p-4
                           sm:grid-cols-3"
                >

                    @if (
                        $statistics[
                            'attendance'
                        ] > 0
                    )

                        @can('attendance_sessions.view')

                            <a
                                href="{{
                                    route(
                                        'attendances.index'
                                    )
                                }}"
                                wire:navigate
                                class="flex items-center
                                       gap-3 rounded-xl
                                       bg-blue-50 p-4
                                       transition
                                       hover:bg-blue-100
                                       dark:bg-blue-950/30
                                       dark:hover:bg-blue-950/50"
                            >
                                <div
                                    class="flex size-10
                                           shrink-0
                                           items-center
                                           justify-center
                                           rounded-xl
                                           bg-blue-100
                                           text-blue-600
                                           dark:bg-blue-900/50
                                           dark:text-blue-300"
                                >
                                    <flux:icon.clipboard-document-check
                                        class="size-5"
                                    />
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="text-sm
                                               font-semibold"
                                    >
                                        {{
                                            $statistics[
                                                'attendance'
                                            ]
                                        }}
                                        absensi aktif
                                    </div>

                                    <div
                                        class="text-xs
                                               text-zinc-500"
                                    >
                                        Cek sesi berlangsung
                                    </div>
                                </div>
                            </a>

                        @endcan

                    @endif


                    @if (
                        $statistics[
                            'journals'
                        ] > 0
                    )

                        @can('journals.view')

                            <a
                                href="{{
                                    route(
                                        'journals.index'
                                    )
                                }}"
                                wire:navigate
                                class="flex items-center
                                       gap-3 rounded-xl
                                       bg-amber-50 p-4
                                       transition
                                       hover:bg-amber-100
                                       dark:bg-amber-950/30"
                            >
                                <div
                                    class="flex size-10
                                           shrink-0
                                           items-center
                                           justify-center
                                           rounded-xl
                                           bg-amber-100
                                           text-amber-600
                                           dark:bg-amber-900/50
                                           dark:text-amber-300"
                                >
                                    <flux:icon.document-text
                                        class="size-5"
                                    />
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="text-sm
                                               font-semibold"
                                    >
                                        {{
                                            $statistics[
                                                'journals'
                                            ]
                                        }}
                                        jurnal
                                    </div>

                                    <div
                                        class="text-xs
                                               text-zinc-500"
                                    >
                                        Belum lengkap
                                    </div>
                                </div>
                            </a>

                        @endcan

                    @endif


                    @if (
                        $statistics[
                            'assessments'
                        ] > 0
                    )

                        @can('activity_assessments.view')

                            <a
                                href="{{
                                    route(
                                        'activity-assessments.index'
                                    )
                                }}"
                                wire:navigate
                                class="flex items-center
                                       gap-3 rounded-xl
                                       bg-violet-50 p-4
                                       transition
                                       hover:bg-violet-100
                                       dark:bg-violet-950/30"
                            >
                                <div
                                    class="flex size-10
                                           shrink-0
                                           items-center
                                           justify-center
                                           rounded-xl
                                           bg-violet-100
                                           text-violet-600
                                           dark:bg-violet-900/50
                                           dark:text-violet-300"
                                >
                                    <flux:icon.academic-cap
                                        class="size-5"
                                    />
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="text-sm
                                               font-semibold"
                                    >
                                        {{
                                            $statistics[
                                                'assessments'
                                            ]
                                        }}
                                        penilaian
                                    </div>

                                    <div
                                        class="text-xs
                                               text-zinc-500"
                                    >
                                        Masih draft
                                    </div>
                                </div>
                            </a>

                        @endcan

                    @endif

                </div>

            </section>

        @endif


        {{-- =================================================
        AGENDA + JURNAL
        ================================================== --}}

        <div
            class="grid gap-5
                   xl:grid-cols-5"
        >

            {{-- =============================================
            AGENDA BERIKUTNYA
            ============================================== --}}

            <section
                class="overflow-hidden
                       rounded-2xl
                       border border-zinc-200
                       bg-white
                       dark:border-zinc-800
                       dark:bg-zinc-900
                       xl:col-span-3"
            >

                <div
                    class="flex items-center
                           justify-between
                           border-b
                           border-zinc-100
                           px-5 py-4
                           dark:border-zinc-800"
                >

                    <div>
                        <h2
                            class="font-semibold
                                   text-zinc-900
                                   dark:text-white"
                        >
                            Agenda Berikutnya
                        </h2>

                        <p
                            class="mt-0.5 text-xs
                                   text-zinc-500
                                   sm:text-sm"
                        >
                            Kegiatan mendatang pada
                            sekolah aktif.
                        </p>
                    </div>


                    @can('activities.view')

                        <a
                            href="{{
                                route(
                                    'activities.index'
                                )
                            }}"
                            wire:navigate
                            class="text-xs
                                   font-semibold
                                   text-emerald-600
                                   hover:text-emerald-700
                                   sm:text-sm"
                        >
                            Semua
                        </a>

                    @endcan

                </div>


                <div
                    class="divide-y
                           divide-zinc-100
                           dark:divide-zinc-800"
                >

                    @forelse (
                        $nextActivities
                        as $activity
                    )

                        <div
                            class="flex gap-4
                                   px-4 py-4
                                   sm:px-5"
                        >

                            {{-- DATE --}}
                            <div
                                class="flex size-12
                                       shrink-0 flex-col
                                       items-center
                                       justify-center
                                       rounded-xl
                                       bg-emerald-50
                                       text-emerald-700
                                       dark:bg-emerald-950/40
                                       dark:text-emerald-300"
                            >
                                <div
                                    class="text-[10px]
                                           font-semibold
                                           uppercase"
                                >
                                    {{
                                        $activity
                                            ->start_at
                                            ->translatedFormat(
                                                'M'
                                            )
                                    }}
                                </div>

                                <div
                                    class="-mt-0.5
                                           text-lg
                                           font-bold"
                                >
                                    {{
                                        $activity
                                            ->start_at
                                            ->format('d')
                                    }}
                                </div>
                            </div>


                            <div
                                class="min-w-0
                                       flex-1"
                            >

                                <div
                                    class="font-semibold
                                           text-zinc-900
                                           dark:text-white"
                                >
                                    {{
                                        $activity
                                            ->title
                                    }}
                                </div>


                                <div
                                    class="mt-1 flex
                                           flex-wrap gap-x-3
                                           gap-y-1
                                           text-xs
                                           text-zinc-500"
                                >

                                    <span
                                        class="inline-flex
                                               items-center
                                               gap-1"
                                    >
                                        <flux:icon.clock
                                            class="size-3.5"
                                        />

                                        {{
                                            $activity
                                                ->start_at
                                                ->format(
                                                    'H:i'
                                                )
                                        }}

                                        @if (
                                            $activity
                                                ->end_at
                                        )
                                            –
                                            {{
                                                $activity
                                                    ->end_at
                                                    ->format(
                                                        'H:i'
                                                    )
                                            }}
                                        @endif
                                    </span>


                                    @if (
                                        $activity
                                            ->location
                                    )
                                        <span
                                            class="inline-flex
                                                   items-center
                                                   gap-1"
                                        >
                                            <flux:icon.map-pin
                                                class="size-3.5"
                                            />

                                            <span
                                                class="max-w-40
                                                       truncate
                                                       sm:max-w-none"
                                            >
                                                {{
                                                    $activity
                                                        ->location
                                                }}
                                            </span>
                                        </span>
                                    @endif

                                </div>


                                @if (
                                    $activity
                                        ->scoutLevels
                                        ->isNotEmpty()
                                )

                                    <div
                                        class="mt-2 flex
                                               flex-wrap gap-1"
                                    >
                                        @foreach (
                                            $activity
                                                ->scoutLevels
                                            as $level
                                        )
                                            <span
                                                class="rounded-full
                                                       bg-zinc-100
                                                       px-2 py-0.5
                                                       text-[11px]
                                                       text-zinc-600
                                                       dark:bg-zinc-800
                                                       dark:text-zinc-300"
                                            >
                                                {{
                                                    $level
                                                        ->name
                                                }}
                                            </span>
                                        @endforeach
                                    </div>

                                @endif

                            </div>

                        </div>

                    @empty

                        <div
                            class="px-5 py-12
                                   text-center"
                        >
                            <div
                                class="mx-auto flex
                                       size-12
                                       items-center
                                       justify-center
                                       rounded-full
                                       bg-zinc-100
                                       text-zinc-400
                                       dark:bg-zinc-800"
                            >
                                <flux:icon.calendar-days
                                    class="size-5"
                                />
                            </div>

                            <div
                                class="mt-3 text-sm
                                       font-medium"
                            >
                                Tidak ada agenda
                                mendatang
                            </div>

                            <p
                                class="mt-1 text-xs
                                       text-zinc-500"
                            >
                                Kegiatan berikutnya
                                akan tampil di sini.
                            </p>
                        </div>

                    @endforelse

                </div>

            </section>


            {{-- =============================================
            JURNAL PERLU DILENGKAPI
            ============================================== --}}

            <section
                class="overflow-hidden
                       rounded-2xl
                       border border-zinc-200
                       bg-white
                       dark:border-zinc-800
                       dark:bg-zinc-900
                       xl:col-span-2"
            >

                <div
                    class="border-b
                           border-zinc-100
                           px-5 py-4
                           dark:border-zinc-800"
                >
                    <h2
                        class="font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        Jurnal Perlu Dilengkapi
                    </h2>

                    <p
                        class="mt-0.5 text-xs
                               text-zinc-500
                               sm:text-sm"
                    >
                        Kegiatan selesai dengan
                        jurnal yang belum lengkap.
                    </p>
                </div>


                <div
                    class="divide-y
                           divide-zinc-100
                           dark:divide-zinc-800"
                >

                    @forelse (
                        $activitiesNeedingJournal
                        as $activity
                    )

                        <div
                            class="flex items-center
                                   gap-3 px-4 py-4
                                   sm:px-5"
                        >

                            <div
                                class="flex size-9
                                       shrink-0
                                       items-center
                                       justify-center
                                       rounded-xl
                                       bg-amber-50
                                       text-amber-600
                                       dark:bg-amber-950/50
                                       dark:text-amber-400"
                            >
                                <flux:icon.document-text
                                    class="size-4"
                                />
                            </div>


                            <div
                                class="min-w-0
                                       flex-1"
                            >
                                <div
                                    class="truncate
                                           text-sm
                                           font-medium
                                           text-zinc-900
                                           dark:text-white"
                                >
                                    {{
                                        $activity
                                            ->title
                                    }}
                                </div>

                                <div
                                    class="mt-0.5
                                           text-xs
                                           text-zinc-500"
                                >
                                    {{
                                        $activity
                                            ->start_at
                                            ->locale(
                                                'id'
                                            )
                                            ->translatedFormat(
                                                'd M Y'
                                            )
                                    }}
                                </div>
                            </div>


                            @if (
                                ! $activity->journal
                            )

                                <span
                                    class="shrink-0
                                           rounded-full
                                           bg-red-50
                                           px-2 py-1
                                           text-[10px]
                                           font-semibold
                                           text-red-600
                                           dark:bg-red-950/40
                                           dark:text-red-300"
                                >
                                    Belum dibuat
                                </span>

                            @else

                                <span
                                    class="shrink-0
                                           rounded-full
                                           bg-amber-50
                                           px-2 py-1
                                           text-[10px]
                                           font-semibold
                                           text-amber-700
                                           dark:bg-amber-950/40
                                           dark:text-amber-300"
                                >
                                    {{
                                        ucfirst(
                                            $activity
                                                ->journal
                                                ->status
                                        )
                                    }}
                                </span>

                            @endif

                        </div>

                    @empty

                        <div
                            class="px-5 py-12
                                   text-center"
                        >

                            <div
                                class="mx-auto flex
                                       size-12
                                       items-center
                                       justify-center
                                       rounded-full
                                       bg-emerald-50
                                       text-emerald-600
                                       dark:bg-emerald-950/40
                                       dark:text-emerald-400"
                            >
                                <flux:icon.check-circle
                                    class="size-5"
                                />
                            </div>

                            <div
                                class="mt-3 text-sm
                                       font-medium"
                            >
                                Semua jurnal lengkap
                            </div>

                            <p
                                class="mt-1 text-xs
                                       text-zinc-500"
                            >
                                Tidak ada jurnal yang
                                perlu ditindaklanjuti.
                            </p>

                        </div>

                    @endforelse

                </div>


                @can('journals.view')

                    <div
                        class="border-t
                               border-zinc-100
                               p-4
                               dark:border-zinc-800"
                    >
                        <a
                            href="{{
                                route(
                                    'journals.index'
                                )
                            }}"
                            wire:navigate
                            class="flex w-full
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-zinc-100
                                   px-4 py-2.5
                                   text-sm
                                   font-medium
                                   text-zinc-700
                                   transition
                                   hover:bg-zinc-200
                                   dark:bg-zinc-800
                                   dark:text-zinc-200
                                   dark:hover:bg-zinc-700"
                        >
                            Buka Jurnal Kegiatan
                        </a>
                    </div>

                @endcan

            </section>

        </div>

    @endif

</div>