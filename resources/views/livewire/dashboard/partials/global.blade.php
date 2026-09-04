{{-- =========================================================
HEADER
========================================================== --}}

<div
    class="flex flex-col gap-4
           lg:flex-row
           lg:items-center
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
            GLOBAL
        </div>

        <h1 class="text-2xl font-semibold">
            Dashboard Global SIMPRAM
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Ringkasan seluruh sekolah yang
            terdaftar pada SIMPRAM.
        </p>

    </div>


    <div
        class="rounded-lg border
               border-zinc-200
               px-4 py-2
               text-sm
               dark:border-zinc-700"
    >
        🌐 Semua Sekolah
    </div>

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
               bg-white p-5 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div class="text-sm text-zinc-500">
            Sekolah Aktif
        </div>

        <div class="mt-2 text-3xl font-semibold">
            {{ number_format(
                $dashboard['activeSchools']
            ) }}
        </div>

        <div class="mt-1 text-xs text-zinc-500">
            dari
            {{ number_format(
                $dashboard['totalSchools']
            ) }}
            sekolah
        </div>
    </div>


    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-5 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div class="text-sm text-zinc-500">
            Siswa Aktif
        </div>

        <div class="mt-2 text-3xl font-semibold">
            {{ number_format(
                $dashboard['totalStudents']
            ) }}
        </div>
    </div>


    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-5 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div class="text-sm text-zinc-500">
            Pembina Aktif
        </div>

        <div class="mt-2 text-3xl font-semibold">
            {{ number_format(
                $dashboard['totalCoaches']
            ) }}
        </div>
    </div>


    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-5 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >
        <div class="text-sm text-zinc-500">
            Pengguna Aktif
        </div>

        <div class="mt-2 text-3xl font-semibold">
            {{ number_format(
                $dashboard['totalUsers']
            ) }}
        </div>
    </div>

</div>


{{-- =========================================================
OPERASIONAL
========================================================== --}}

<section
    class="rounded-xl border
           border-zinc-200
           bg-white p-6 shadow-sm
           dark:border-zinc-800
           dark:bg-zinc-900"
>

    <h2 class="text-lg font-semibold">
        Operasional
    </h2>


    <div
        class="mt-5 grid gap-4
               sm:grid-cols-2
               lg:grid-cols-3
               xl:grid-cols-6"
    >

        @php
            $operationalCards = [
                'Regu / Barung' =>
                    $dashboard[
                        'totalScoutUnits'
                    ],

                'Total Kegiatan' =>
                    $dashboard[
                        'totalActivities'
                    ],

                'Kegiatan Bulan Ini' =>
                    $dashboard[
                        'activitiesThisMonth'
                    ],

                'Jurnal' =>
                    $dashboard[
                        'totalJournals'
                    ],

                'Jurnal Terbit' =>
                    $dashboard[
                        'publishedJournals'
                    ],

                'Pengumuman' =>
                    $dashboard[
                        'publishedAnnouncements'
                    ],
            ];
        @endphp


        @foreach (
            $operationalCards
            as $label => $value
        )

            <div
                class="rounded-lg border
                       border-zinc-200 p-4
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

    </div>

</section>


{{-- =========================================================
ABSENSI + NILAI
========================================================== --}}

<div class="grid gap-4 lg:grid-cols-2">

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <h2 class="text-lg font-semibold">
            Kehadiran Global
        </h2>


        <div class="mt-5">

            <div class="text-4xl font-semibold">

                {{ number_format(
                    $dashboard[
                        'globalPresencePercentage'
                    ],
                    2
                ) }}%

            </div>

            <p class="mt-1 text-sm text-zinc-500">
                Kehadiran fisik dari seluruh
                slot peserta yang tercatat.
            </p>

        </div>


        <div class="mt-5">

            <div
                class="h-3 overflow-hidden
                       rounded-full bg-zinc-100
                       dark:bg-zinc-800"
            >
                <div
                    class="h-full rounded-full
                           bg-zinc-900
                           dark:bg-white"
                    style="
                        width:
                        {{ min(
                            100,
                            $dashboard[
                                'globalPresencePercentage'
                            ]
                        ) }}%
                    "
                ></div>
            </div>

        </div>

    </section>


    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <h2 class="text-lg font-semibold">
            Penilaian Global
        </h2>


        <div class="mt-5 grid grid-cols-2 gap-5">

            <div>

                <div class="text-4xl font-semibold">
                    {{ number_format(
                        $dashboard[
                            'averageFinalGrade'
                        ],
                        2
                    ) }}
                </div>

                <div class="mt-1 text-sm text-zinc-500">
                    Rata-rata nilai
                </div>

            </div>


            <div>

                <div class="text-4xl font-semibold">
                    {{ number_format(
                        $dashboard[
                            'gradedStudents'
                        ]
                    ) }}
                </div>

                <div class="mt-1 text-sm text-zinc-500">
                    Siswa telah dinilai
                </div>

            </div>

        </div>

    </section>

</div>


{{-- =========================================================
TELEGRAM
========================================================== --}}

<section
    class="rounded-xl border
           border-zinc-200
           bg-white p-6 shadow-sm
           dark:border-zinc-800
           dark:bg-zinc-900"
>

    <h2 class="text-lg font-semibold">
        Notifikasi Telegram
    </h2>


    <div
        class="mt-5 grid gap-4
               sm:grid-cols-3"
    >

        <div
            class="rounded-lg border
                   border-zinc-200 p-4
                   dark:border-zinc-700"
        >

            <div class="text-2xl font-semibold">
                {{ number_format(
                    $dashboard[
                        'telegramLinked'
                    ]
                ) }}
            </div>

            <div class="text-xs text-zinc-500">
                Akun Terhubung
            </div>

        </div>


        <div
            class="rounded-lg border
                   border-zinc-200 p-4
                   dark:border-zinc-700"
        >

            <div class="text-2xl font-semibold">
                {{ number_format(
                    $dashboard[
                        'telegramSent'
                    ]
                ) }}
            </div>

            <div class="text-xs text-zinc-500">
                Terkirim
            </div>

        </div>


        <div
            class="rounded-lg border
                   border-zinc-200 p-4
                   dark:border-zinc-700"
        >

            <div class="text-2xl font-semibold">
                {{ number_format(
                    $dashboard[
                        'telegramFailed'
                    ]
                ) }}
            </div>

            <div class="text-xs text-zinc-500">
                Gagal
            </div>

        </div>

    </div>

</section>


{{-- =========================================================
AKTIVITAS 6 BULAN
========================================================== --}}

<section
    class="rounded-xl border
           border-zinc-200
           bg-white p-6 shadow-sm
           dark:border-zinc-800
           dark:bg-zinc-900"
>

    <h2 class="text-lg font-semibold">
        Kegiatan 6 Bulan Terakhir
    </h2>


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
                        style="
                            width:
                            {{ $width }}%
                        "
                    ></div>

                </div>

            </div>

        @endforeach

    </div>

</section>


{{-- =========================================================
MONITORING SEKOLAH
========================================================== --}}

<section
    class="rounded-xl border
           border-zinc-200
           bg-white shadow-sm
           dark:border-zinc-800
           dark:bg-zinc-900"
>

    <div class="p-6">

        <h2 class="text-lg font-semibold">
            Monitoring Sekolah
        </h2>

        <p class="mt-1 text-sm text-zinc-500">
            Ringkasan seluruh sekolah aktif.
        </p>

    </div>


    <div class="overflow-x-auto">

        <table class="w-full text-left text-sm">

            <thead>

                <tr
                    class="border-y
                           border-zinc-200
                           text-zinc-500
                           dark:border-zinc-800"
                >

                    <th class="p-3">
                        Sekolah
                    </th>

                    <th class="p-3">
                        Siswa
                    </th>

                    <th class="p-3">
                        Pembina
                    </th>

                    <th class="p-3">
                        Kegiatan
                    </th>

                    <th class="p-3">
                        Jurnal
                    </th>

                    <th class="p-3">
                        Kehadiran
                    </th>

                    <th class="p-3">
                        Rata-rata Nilai
                    </th>

                    <th class="p-3">
                        Aksi
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse (
                    $dashboard[
                        'schoolMonitoring'
                    ]
                    as $row
                )

                    <tr
                        class="border-b
                               border-zinc-100
                               dark:border-zinc-800"
                    >

                        <td class="p-3">

                            <div class="font-medium">
                                {{ $row[
                                    'school'
                                ]->name }}
                            </div>

                            <div class="text-xs text-zinc-500">

                                {{ $row[
                                    'school'
                                ]->npsn ?: '-'
                                }}

                            </div>

                        </td>


                        <td class="p-3">
                            {{ $row['students'] }}
                        </td>


                        <td class="p-3">
                            {{ $row['coaches'] }}
                        </td>


                        <td class="p-3">
                            {{ $row['activities'] }}
                        </td>


                        <td class="p-3">
                            {{ $row['journals'] }}
                        </td>


                        <td class="p-3">
                            {{ number_format(
                                $row['presence'],
                                2
                            ) }}%
                        </td>


                        <td class="p-3">
                            {{ number_format(
                                $row[
                                    'average_grade'
                                ],
                                2
                            ) }}
                        </td>


                        <td class="p-3">

                            <form
                                method="POST"
                                action="{{ route(
                                    'school.switch'
                                ) }}"
                            >

                                @csrf

                                <input
                                    type="hidden"
                                    name="school_id"
                                    value="{{ $row[
                                        'school'
                                    ]->id }}"
                                >

                                <button
                                    type="submit"
                                    class="rounded-lg
                                           border
                                           border-zinc-300
                                           px-3 py-1.5
                                           text-sm
                                           font-medium
                                           dark:border-zinc-700"
                                >
                                    Buka Sekolah
                                </button>

                            </form>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="8"
                            class="p-8 text-center
                                   text-zinc-500"
                        >
                            Belum ada sekolah aktif.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</section>


{{-- =========================================================
AGENDA + PENGUMUMAN
========================================================== --}}

<div class="grid gap-4 xl:grid-cols-2">

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <h2 class="text-lg font-semibold">
            Agenda Mendatang
        </h2>


        <div class="mt-5 space-y-3">

            @forelse (
                $dashboard[
                    'upcomingActivities'
                ]
                as $activity
            )

                <div
                    class="rounded-lg border
                           border-zinc-200 p-4
                           dark:border-zinc-700"
                >

                    <div class="font-medium">
                        {{ $activity->title }}
                    </div>

                    <div
                        class="mt-1 text-xs
                               text-zinc-500"
                    >
                        {{ $activity->school_name }}
                    </div>

                    <div
                        class="mt-1 text-sm
                               text-zinc-500"
                    >
                        {{ \Carbon\Carbon::parse(
                            $activity->start_at
                        )->format(
                            'd-m-Y H:i'
                        ) }}
                    </div>

                </div>

            @empty

                <div class="py-8 text-center text-zinc-500">
                    Belum ada agenda mendatang.
                </div>

            @endforelse

        </div>

    </section>


    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <h2 class="text-lg font-semibold">
            Pengumuman Terbaru
        </h2>


        <div class="mt-5 space-y-3">

            @forelse (
                $dashboard[
                    'latestAnnouncements'
                ]
                as $announcement
            )

                <div
                    class="rounded-lg border
                           border-zinc-200 p-4
                           dark:border-zinc-700"
                >

                    <div class="font-medium">
                        {{ $announcement->title }}
                    </div>


                    <div
                        class="mt-1 text-xs
                               text-zinc-500"
                    >
                        {{ $announcement
                            ->school_name }}
                    </div>


                    <p
                        class="mt-2 text-sm
                               text-zinc-600
                               dark:text-zinc-300"
                    >
                        {{ \Illuminate\Support\Str::limit(
                            $announcement->body,
                            120
                        ) }}
                    </p>

                </div>

            @empty

                <div class="py-8 text-center text-zinc-500">
                    Belum ada pengumuman.
                </div>

            @endforelse

        </div>

    </section>

</div>
