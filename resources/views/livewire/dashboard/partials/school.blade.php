{{-- =========================================================
HEADER DASHBOARD SEKOLAH
========================================================== --}}

<div
    class="flex flex-col gap-4
           lg:flex-row
           lg:items-start
           lg:justify-between"
>
    <div>
        <div
            class="mb-2 inline-flex
                   rounded-full
                   bg-zinc-100
                   px-3 py-1
                   text-xs font-medium
                   dark:bg-zinc-800"
        >
            SEKOLAH AKTIF
        </div>

        <h1 class="text-2xl font-semibold">
            Dashboard Gugus Depan
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            {{ $school->name }}
        </p>

        @if ($dashboard['academicYear'])
            <p class="mt-1 text-sm text-zinc-500">
                Tahun Ajaran
                {{ $dashboard['academicYear']->name }}

                @if ($dashboard['semester'])
                    · {{ $dashboard['semester']->name }}
                @endif
            </p>
        @else
            <p class="mt-1 text-sm text-amber-600">
                Belum ada tahun ajaran aktif.
            </p>
        @endif
    </div>


    @if (auth()->user()->isSuperAdmin())
        <form
            method="POST"
            action="{{ route('school.switch') }}"
        >
            @csrf

            <input
                type="hidden"
                name="school_id"
                value="global"
            >

            <button
                type="submit"
                class="rounded-lg border
                       border-zinc-300
                       px-4 py-2
                       text-sm font-medium
                       hover:bg-zinc-50
                       dark:border-zinc-700
                       dark:hover:bg-zinc-800"
            >
                🌐 Kembali ke Dashboard Global
            </button>
        </form>
    @endif
</div>


{{-- =========================================================
STATISTIK UTAMA
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
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div class="text-sm text-zinc-500">
            Siswa Aktif
        </div>

        <div class="mt-2 text-3xl font-semibold">
            {{ number_format(
                $dashboard['studentCount']
            ) }}
        </div>

        @can('students.view')
            <a
                href="{{ route('students.index') }}"
                wire:navigate
                class="mt-3 inline-block
                       text-xs font-medium
                       text-zinc-600 underline
                       dark:text-zinc-300"
            >
                Lihat siswa
            </a>
        @endcan
    </div>


    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-5
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div class="text-sm text-zinc-500">
            Pembina Aktif
        </div>

        <div class="mt-2 text-3xl font-semibold">
            {{ number_format(
                $dashboard['coachCount']
            ) }}
        </div>

        @can('coaches.view')
            <a
                href="{{ route('coaches.index') }}"
                wire:navigate
                class="mt-3 inline-block
                       text-xs font-medium
                       text-zinc-600 underline
                       dark:text-zinc-300"
            >
                Lihat pembina
            </a>
        @endcan
    </div>


    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-5
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div class="text-sm text-zinc-500">
            Regu / Barung
        </div>

        <div class="mt-2 text-3xl font-semibold">
            {{ number_format(
                $dashboard['scoutUnitCount']
            ) }}
        </div>

        @can('scout_units.view')
            <a
                href="{{ route('scout-units.index') }}"
                wire:navigate
                class="mt-3 inline-block
                       text-xs font-medium
                       text-zinc-600 underline
                       dark:text-zinc-300"
            >
                Lihat Regu / Barung
            </a>
        @endcan
    </div>


    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-5
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div class="text-sm text-zinc-500">
            Kegiatan Periode Ini
        </div>

        <div class="mt-2 text-3xl font-semibold">
            {{ number_format(
                $dashboard['activityCount']
            ) }}
        </div>

        @can('activities.view')
            <a
                href="{{ route('activities.index') }}"
                wire:navigate
                class="mt-3 inline-block
                       text-xs font-medium
                       text-zinc-600 underline
                       dark:text-zinc-300"
            >
                Lihat kegiatan
            </a>
        @endcan
    </div>
</div>


{{-- =========================================================
KEHADIRAN
========================================================== --}}

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
               md:flex-row
               md:items-center
               md:justify-between"
    >
        <div>
            <h2 class="text-lg font-semibold">
                Kehadiran
            </h2>

            <p class="mt-1 text-sm text-zinc-500">
                Ringkasan seluruh sesi absensi
                pada periode aktif.
            </p>
        </div>

        @can('reports.attendance.view')
            <a
                href="{{ route(
                    'reports.attendance'
                ) }}"
                wire:navigate
                class="text-sm font-medium underline"
            >
                Lihat Rekap Absensi
            </a>
        @endcan
    </div>


    <div
        class="mt-6 grid gap-3
               sm:grid-cols-2
               md:grid-cols-4
               xl:grid-cols-7"
    >
        @php
            $attendanceCards = [
                'Hadir' => $dashboard['present'],
                'Terlambat' => $dashboard['late'],
                'Sakit' => $dashboard['sick'],
                'Izin' => $dashboard['excused'],
                'Alpa' => $dashboard['absent'],
                'Belum Dicatat' => $dashboard['unrecorded'],
            ];
        @endphp


        @foreach (
            $attendanceCards
            as $label => $value
        )
            <div
                class="rounded-lg border
                       border-zinc-200
                       p-4
                       dark:border-zinc-700"
            >
                <div class="text-2xl font-semibold">
                    {{ number_format($value) }}
                </div>

                <div
                    class="mt-1 text-xs
                           text-zinc-500"
                >
                    {{ $label }}
                </div>
            </div>
        @endforeach


        <div
            class="rounded-lg border
                   border-zinc-200
                   p-4
                   dark:border-zinc-700"
        >
            <div class="text-2xl font-semibold">
                {{ number_format(
                    $dashboard[
                        'presencePercentage'
                    ],
                    2
                ) }}%
            </div>

            <div
                class="mt-1 text-xs
                       text-zinc-500"
            >
                Kehadiran Fisik
            </div>
        </div>
    </div>


    <div class="mt-5">
        <div
            class="mb-2 flex
                   justify-between
                   text-xs
                   text-zinc-500"
        >
            <span>
                Persentase Kehadiran
            </span>

            <span>
                {{ number_format(
                    $dashboard[
                        'presencePercentage'
                    ],
                    2
                ) }}%
            </span>
        </div>


        <div
            class="h-2 overflow-hidden
                   rounded-full
                   bg-zinc-100
                   dark:bg-zinc-800"
        >
            <div
                class="h-full rounded-full
                       bg-zinc-900
                       dark:bg-white"
                style="width: {{ min(
                    100,
                    $dashboard[
                        'presencePercentage'
                    ]
                ) }}%"
            ></div>
        </div>
    </div>
</section>


{{-- =========================================================
JURNAL + PENILAIAN
========================================================== --}}

<div class="grid gap-4 lg:grid-cols-2">

    {{-- JURNAL --}}

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div
            class="flex items-center
                   justify-between"
        >
            <div>
                <h2 class="text-lg font-semibold">
                    Jurnal Kegiatan
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Administrasi jurnal
                    kegiatan periode aktif.
                </p>
            </div>

            @can('journals.view')
                <a
                    href="{{ route(
                        'journals.index'
                    ) }}"
                    wire:navigate
                    class="text-sm underline"
                >
                    Lihat Semua
                </a>
            @endcan
        </div>


        <div
            class="mt-6
                   grid grid-cols-2
                   gap-4"
        >
            <div>
                <div class="text-3xl font-semibold">
                    {{ number_format(
                        $dashboard[
                            'journalCount'
                        ]
                    ) }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Total jurnal
                </div>
            </div>


            <div>
                <div class="text-3xl font-semibold">
                    {{ number_format(
                        $dashboard[
                            'publishedJournalCount'
                        ]
                    ) }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Dipublikasikan
                </div>
            </div>
        </div>


        @php
            $journalPercentage =
                $dashboard['journalCount'] > 0
                    ? round(
                        (
                            $dashboard[
                                'publishedJournalCount'
                            ]
                            /
                            $dashboard[
                                'journalCount'
                            ]
                        ) * 100,
                        2
                    )
                    : 0;
        @endphp


        <div class="mt-5">
            <div
                class="mb-2 flex
                       justify-between
                       text-xs
                       text-zinc-500"
            >
                <span>
                    Jurnal dipublikasikan
                </span>

                <span>
                    {{ number_format(
                        $journalPercentage,
                        2
                    ) }}%
                </span>
            </div>

            <div
                class="h-2 overflow-hidden
                       rounded-full
                       bg-zinc-100
                       dark:bg-zinc-800"
            >
                <div
                    class="h-full rounded-full
                           bg-zinc-900
                           dark:bg-white"
                    style="width: {{ min(
                        100,
                        $journalPercentage
                    ) }}%"
                ></div>
            </div>
        </div>
    </section>


    {{-- PENILAIAN --}}

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div
            class="flex items-center
                   justify-between"
        >
            <div>
                <h2 class="text-lg font-semibold">
                    Penilaian
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Ringkasan hasil penilaian
                    Pramuka.
                </p>
            </div>

            @can('reports.grades.view')
                <a
                    href="{{ route(
                        'reports.grades'
                    ) }}"
                    wire:navigate
                    class="text-sm underline"
                >
                    Rekap Nilai
                </a>
            @endcan
        </div>


        <div
            class="mt-6
                   grid grid-cols-2
                   gap-4"
        >
            <div>
                <div class="text-3xl font-semibold">
                    {{ number_format(
                        $dashboard[
                            'averageFinalGrade'
                        ],
                        2
                    ) }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Rata-rata nilai
                </div>
            </div>


            <div>
                <div class="text-3xl font-semibold">
                    {{ number_format(
                        $dashboard[
                            'gradedStudentCount'
                        ]
                    ) }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Siswa telah dinilai
                </div>
            </div>
        </div>


        @can('assessments.scores.view')
            <a
                href="{{ route(
                    'assessments.scores'
                ) }}"
                wire:navigate
                class="mt-5 inline-block
                       text-sm font-medium
                       underline"
            >
                Kelola Penilaian
            </a>
        @endcan
    </section>
</div>


{{-- =========================================================
AKTIVITAS 6 BULAN
========================================================== --}}

<section
    class="rounded-xl border
           border-zinc-200
           bg-white p-6
           shadow-sm
           dark:border-zinc-800
           dark:bg-zinc-900"
>
    <div
        class="flex flex-col gap-2
               sm:flex-row
               sm:items-center
               sm:justify-between"
    >
        <div>
            <h2 class="text-lg font-semibold">
                Aktivitas 6 Bulan Terakhir
            </h2>

            <p
                class="mt-1 text-sm
                       text-zinc-500"
            >
                Jumlah agenda kegiatan setiap bulan.
            </p>
        </div>

        <div
            class="rounded-lg
                   bg-zinc-100
                   px-3 py-2
                   text-sm
                   dark:bg-zinc-800"
        >
            Total:
            <strong>
                {{ number_format(
                    $dashboard[
                        'activityByMonth'
                    ]->sum('total')
                ) }}
            </strong>
        </div>
    </div>


    @php
        $maxActivity =
            max(
                1,
                $dashboard[
                    'activityByMonth'
                ]->max('total')
            );
    @endphp


    <div class="mt-6 space-y-4">

        @foreach (
            $dashboard['activityByMonth']
            as $month
        )
            @php
                $width =
                    (
                        $month['total']
                        /
                        $maxActivity
                    )
                    * 100;
            @endphp


            <div>
                <div
                    class="mb-1 flex
                           justify-between
                           text-sm"
                >
                    <span>
                        {{ $month['label'] }}
                    </span>

                    <span class="font-medium">
                        {{ $month['total'] }}
                    </span>
                </div>


                <div
                    class="h-2 overflow-hidden
                           rounded-full
                           bg-zinc-100
                           dark:bg-zinc-800"
                >
                    <div
                        class="h-full rounded-full
                               bg-zinc-900
                               dark:bg-white"
                        style="width: {{ $width }}%"
                    ></div>
                </div>
            </div>
        @endforeach

    </div>
</section>


{{-- =========================================================
AGENDA MENDATANG + PENGUMUMAN
========================================================== --}}

<div class="grid gap-4 xl:grid-cols-2">

    {{-- AGENDA MENDATANG --}}

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div
            class="flex items-center
                   justify-between"
        >
            <div>
                <h2 class="text-lg font-semibold">
                    Agenda Mendatang
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Maksimal 5 agenda terdekat.
                </p>
            </div>

            @can('activities.view')
                <a
                    href="{{ route(
                        'activities.index'
                    ) }}"
                    wire:navigate
                    class="text-sm underline"
                >
                    Lihat Semua
                </a>
            @endcan
        </div>


        <div class="mt-5 space-y-3">

            @forelse (
                $dashboard[
                    'upcomingActivities'
                ]
                as $activity
            )
                <div
                    class="rounded-lg border
                           border-zinc-200
                           p-4
                           dark:border-zinc-700"
                >
                    <div
                        class="flex flex-col
                               gap-2
                               sm:flex-row
                               sm:items-start
                               sm:justify-between"
                    >
                        <div>
                            <div class="font-medium">
                                {{ $activity->title }}
                            </div>

                            <div
                                class="mt-1 text-sm
                                       text-zinc-500"
                            >
                                {{ $activity
                                    ->start_at
                                    ->format(
                                        'd-m-Y H:i'
                                    )
                                }}
                            </div>

                            @if ($activity->location)
                                <div
                                    class="mt-1
                                           text-xs
                                           text-zinc-500"
                                >
                                    {{ $activity->location }}
                                </div>
                            @endif
                        </div>


                        @if (
                            $activity->status
                            === 'published'
                        )
                            <span
                                class="rounded-full
                                       bg-green-100
                                       px-2 py-1
                                       text-xs
                                       font-medium
                                       text-green-700"
                            >
                                Published
                            </span>
                        @elseif (
                            $activity->status
                            === 'draft'
                        )
                            <span
                                class="rounded-full
                                       bg-amber-100
                                       px-2 py-1
                                       text-xs
                                       font-medium
                                       text-amber-700"
                            >
                                Draft
                            </span>
                        @endif
                    </div>


                    @if ($activity->coaches->isNotEmpty())
                        <div
                            class="mt-3
                                   text-xs
                                   text-zinc-500"
                        >
                            Pembina:
                            {{ $activity
                                ->coaches
                                ->pluck('name')
                                ->join(', ')
                            }}
                        </div>
                    @endif
                </div>

            @empty
                <div
                    class="rounded-lg
                           border border-dashed
                           border-zinc-300
                           py-10
                           text-center
                           text-sm
                           text-zinc-500
                           dark:border-zinc-700"
                >
                    Belum ada agenda mendatang.
                </div>
            @endforelse

        </div>
    </section>


    {{-- PENGUMUMAN TERBARU --}}

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div
            class="flex items-center
                   justify-between"
        >
            <div>
                <h2 class="text-lg font-semibold">
                    Pengumuman Terbaru
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Informasi terbaru di sekolah.
                </p>
            </div>


            @if (
                Route::has(
                    'announcements.my'
                )
            )
                <a
                    href="{{ route(
                        'announcements.my'
                    ) }}"
                    wire:navigate
                    class="text-sm underline"
                >
                    Lihat Semua
                </a>
            @endif
        </div>


        <div class="mt-5 space-y-3">

            @forelse (
                $dashboard[
                    'latestAnnouncements'
                ]
                as $announcement
            )
                <article
                    class="rounded-lg border
                           border-zinc-200
                           p-4
                           dark:border-zinc-700"
                >
                    <div
                        class="flex items-start
                               justify-between
                               gap-3"
                    >
                        <div class="font-medium">
                            {{ $announcement->title }}
                        </div>

                        @if (
                            $announcement->is_public
                        )
                            <span
                                class="rounded-full
                                       bg-zinc-100
                                       px-2 py-1
                                       text-xs
                                       dark:bg-zinc-800"
                            >
                                Publik
                            </span>
                        @endif
                    </div>


                    <div
                        class="mt-1
                               text-xs
                               text-zinc-500"
                    >
                        {{ $announcement
                            ->published_at
                            ?->format(
                                'd-m-Y H:i'
                            )
                        }}
                    </div>


                    <p
                        class="mt-2 text-sm
                               leading-6
                               text-zinc-600
                               dark:text-zinc-300"
                    >
                        {{ \Illuminate\Support\Str::limit(
                            $announcement->body,
                            150
                        ) }}
                    </p>
                </article>

            @empty
                <div
                    class="rounded-lg
                           border border-dashed
                           border-zinc-300
                           py-10
                           text-center
                           text-sm
                           text-zinc-500
                           dark:border-zinc-700"
                >
                    Belum ada pengumuman terbaru.
                </div>
            @endforelse

        </div>
    </section>

</div>


{{-- =========================================================
QUICK ACTION
========================================================== --}}

<section
    class="rounded-xl border
           border-zinc-200
           bg-white p-6
           shadow-sm
           dark:border-zinc-800
           dark:bg-zinc-900"
>
    <h2 class="text-lg font-semibold">
        Akses Cepat
    </h2>

    <p
        class="mt-1 text-sm
               text-zinc-500"
    >
        Buka modul operasional yang paling
        sering digunakan.
    </p>


    <div
        class="mt-5 grid gap-3
               sm:grid-cols-2
               lg:grid-cols-4"
    >
        @can('activities.view')
            <a
                href="{{ route(
                    'activities.index'
                ) }}"
                wire:navigate
                class="rounded-lg border
                       border-zinc-200
                       p-4
                       transition
                       hover:bg-zinc-50
                       dark:border-zinc-700
                       dark:hover:bg-zinc-800"
            >
                <div class="font-medium">
                    Agenda / Kegiatan
                </div>

                <div
                    class="mt-1 text-xs
                           text-zinc-500"
                >
                    Kelola kegiatan Pramuka
                </div>
            </a>
        @endcan


        @can('journals.view')
            <a
                href="{{ route(
                    'journals.index'
                ) }}"
                wire:navigate
                class="rounded-lg border
                       border-zinc-200
                       p-4
                       transition
                       hover:bg-zinc-50
                       dark:border-zinc-700
                       dark:hover:bg-zinc-800"
            >
                <div class="font-medium">
                    Jurnal
                </div>

                <div
                    class="mt-1 text-xs
                           text-zinc-500"
                >
                    Dokumentasi kegiatan
                </div>
            </a>
        @endcan


        @can('assessments.scores.view')
            <a
                href="{{ route(
                    'assessments.scores'
                ) }}"
                wire:navigate
                class="rounded-lg border
                       border-zinc-200
                       p-4
                       transition
                       hover:bg-zinc-50
                       dark:border-zinc-700
                       dark:hover:bg-zinc-800"
            >
                <div class="font-medium">
                    Input Nilai
                </div>

                <div
                    class="mt-1 text-xs
                           text-zinc-500"
                >
                    Penilaian siswa
                </div>
            </a>
        @endcan


        @can('reports.attendance.view')
            <a
                href="{{ route(
                    'reports.attendance'
                ) }}"
                wire:navigate
                class="rounded-lg border
                       border-zinc-200
                       p-4
                       transition
                       hover:bg-zinc-50
                       dark:border-zinc-700
                       dark:hover:bg-zinc-800"
            >
                <div class="font-medium">
                    Rekap Absensi
                </div>

                <div
                    class="mt-1 text-xs
                           text-zinc-500"
                >
                    Monitoring kehadiran
                </div>
            </a>
        @endcan
    </div>
</section>