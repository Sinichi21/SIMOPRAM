<div class="mx-auto max-w-7xl space-y-6">

    @if (! $student)

        {{-- ================================================
        AKUN BELUM TERHUBUNG KE DATA SISWA
        ================================================= --}}

        <div
            class="rounded-2xl border border-amber-200
                   bg-amber-50 p-6
                   dark:border-amber-900/50
                   dark:bg-amber-950/30"
        >
            <div class="flex items-start gap-4">

                <div
                    class="flex size-11 shrink-0 items-center
                           justify-center rounded-xl
                           bg-amber-100 text-amber-700
                           dark:bg-amber-900/50
                           dark:text-amber-300"
                >
                    <flux:icon.exclamation-triangle
                        class="size-5"
                    />
                </div>

                <div>
                    <h2
                        class="text-base font-semibold
                               text-zinc-900 dark:text-white"
                    >
                        Data siswa belum terhubung
                    </h2>

                    <p
                        class="mt-1 text-sm leading-6
                               text-zinc-600
                               dark:text-zinc-400"
                    >
                        Akun Anda sudah aktif, tetapi belum
                        terhubung dengan data siswa pada sekolah.
                        Silakan hubungi administrator sekolah.
                    </p>
                </div>

            </div>
        </div>

    @else

        {{-- ================================================
        HEADER
        ================================================= --}}

        <section
            class="overflow-hidden rounded-2xl
                   border border-zinc-200
                   bg-white
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >
            <div
                class="relative overflow-hidden
                       bg-gradient-to-br
                       from-emerald-700
                       via-emerald-600
                       to-teal-600
                       px-5 py-7
                       text-white
                       sm:px-7"
            >

                <div
                    class="absolute -right-16 -top-20
                           size-56 rounded-full
                           bg-white/10"
                ></div>

                <div
                    class="absolute -bottom-24 right-28
                           size-48 rounded-full
                           bg-white/5"
                ></div>

                <div class="relative">

                    <p
                        class="text-sm font-medium
                               text-emerald-100"
                    >
                        Selamat datang kembali
                    </p>

                    <h1
                        class="mt-1 text-2xl font-bold
                               tracking-tight sm:text-3xl"
                    >
                        {{ $student->name }}
                    </h1>

                    <div
                        class="mt-4 flex flex-wrap
                               items-center gap-x-5
                               gap-y-2 text-sm
                               text-emerald-50"
                    >

                        @if ($enrollment?->classroom)
                            <span
                                class="inline-flex
                                       items-center gap-2"
                            >
                                <flux:icon.academic-cap
                                    class="size-4"
                                />

                                Kelas
                                {{ $enrollment->classroom->name }}
                            </span>
                        @endif

                        @if ($student->school)
                            <span
                                class="inline-flex
                                       items-center gap-2"
                            >
                                <flux:icon.building-office-2
                                    class="size-4"
                                />

                                {{ $student->school->name }}
                            </span>
                        @endif

                        @if ($student->nis)
                            <span>
                                NIS {{ $student->nis }}
                            </span>
                        @endif

                    </div>

                </div>
            </div>
        </section>


        {{-- ================================================
        RINGKASAN
        ================================================= --}}

        <section>

            <div class="mb-3">
                <h2
                    class="text-base font-semibold
                           text-zinc-900 dark:text-white"
                >
                    Ringkasan Saya
                </h2>

                <p
                    class="mt-1 text-sm text-zinc-500
                           dark:text-zinc-400"
                >
                    Ringkasan kehadiran kegiatan Pramuka.
                </p>
            </div>

            <div
                class="grid grid-cols-2 gap-3
                       lg:grid-cols-4"
            >

                {{-- Persentase --}}
                <div
                    class="rounded-2xl border
                           border-zinc-200 bg-white p-5
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >
                    <div
                        class="flex items-center
                               justify-between"
                    >
                        <div
                            class="flex size-10 items-center
                                   justify-center rounded-xl
                                   bg-emerald-50
                                   text-emerald-600
                                   dark:bg-emerald-950/50
                                   dark:text-emerald-400"
                        >
                            <flux:icon.chart-bar
                                class="size-5"
                            />
                        </div>
                    </div>

                    <div
                        class="mt-4 text-2xl font-bold
                               text-zinc-900
                               dark:text-white"
                    >
                        @if (
                            $attendance['percentage']
                            !== null
                        )
                            {{ number_format(
                                $attendance['percentage'],
                                1
                            ) }}%
                        @else
                            -
                        @endif
                    </div>

                    <div
                        class="mt-1 text-sm text-zinc-500
                               dark:text-zinc-400"
                    >
                        Kehadiran
                    </div>
                </div>


                {{-- Hadir --}}
                <div
                    class="rounded-2xl border
                           border-zinc-200 bg-white p-5
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >
                    <div
                        class="flex size-10 items-center
                               justify-center rounded-xl
                               bg-blue-50 text-blue-600
                               dark:bg-blue-950/50
                               dark:text-blue-400"
                    >
                        <flux:icon.check-circle
                            class="size-5"
                        />
                    </div>

                    <div
                        class="mt-4 text-2xl font-bold
                               text-zinc-900
                               dark:text-white"
                    >
                        {{
                            $attendance['present']
                            + $attendance['late']
                        }}
                    </div>

                    <div
                        class="mt-1 text-sm text-zinc-500
                               dark:text-zinc-400"
                    >
                        Hadir
                    </div>
                </div>


                {{-- Izin dan sakit --}}
                <div
                    class="rounded-2xl border
                           border-zinc-200 bg-white p-5
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >
                    <div
                        class="flex size-10 items-center
                               justify-center rounded-xl
                               bg-amber-50 text-amber-600
                               dark:bg-amber-950/50
                               dark:text-amber-400"
                    >
                        <flux:icon.clock
                            class="size-5"
                        />
                    </div>

                    <div
                        class="mt-4 text-2xl font-bold
                               text-zinc-900
                               dark:text-white"
                    >
                        {{
                            $attendance['excused']
                            + $attendance['sick']
                        }}
                    </div>

                    <div
                        class="mt-1 text-sm text-zinc-500
                               dark:text-zinc-400"
                    >
                        Izin / Sakit
                    </div>
                </div>


                {{-- Alpa --}}
                <div
                    class="rounded-2xl border
                           border-zinc-200 bg-white p-5
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >
                    <div
                        class="flex size-10 items-center
                               justify-center rounded-xl
                               bg-red-50 text-red-600
                               dark:bg-red-950/50
                               dark:text-red-400"
                    >
                        <flux:icon.x-circle
                            class="size-5"
                        />
                    </div>

                    <div
                        class="mt-4 text-2xl font-bold
                               text-zinc-900
                               dark:text-white"
                    >
                        {{ $attendance['absent'] }}
                    </div>

                    <div
                        class="mt-1 text-sm text-zinc-500
                               dark:text-zinc-400"
                    >
                        Alpa
                    </div>
                </div>

            </div>

        </section>


        {{-- ================================================
        AGENDA + AKSI CEPAT
        ================================================= --}}

        <div
            class="grid gap-6
                   lg:grid-cols-3"
        >

            {{-- AGENDA --}}
            <section
                id="agenda"
                class="rounded-2xl border
                       border-zinc-200 bg-white
                       dark:border-zinc-800
                       dark:bg-zinc-900
                       lg:col-span-2"
            >

                <div
                    class="flex items-center
                           justify-between
                           border-b border-zinc-100
                           px-5 py-4
                           dark:border-zinc-800"
                >
                    <div>
                        <h2
                            class="font-semibold
                                   text-zinc-900
                                   dark:text-white"
                        >
                            Kegiatan Berikutnya
                        </h2>

                        <p
                            class="mt-0.5 text-sm
                                   text-zinc-500
                                   dark:text-zinc-400"
                        >
                            Agenda Pramuka terdekat.
                        </p>
                    </div>

                    <div
                        class="flex size-10 items-center
                               justify-center rounded-xl
                               bg-emerald-50
                               text-emerald-600
                               dark:bg-emerald-950/50
                               dark:text-emerald-400"
                    >
                        <flux:icon.calendar-days
                            class="size-5"
                        />
                    </div>
                </div>

                <div class="p-5">

                    @if ($nextActivity)

                        <div
                            class="rounded-xl
                                   bg-zinc-50 p-5
                                   dark:bg-zinc-800/70"
                        >

                            <div
                                class="flex flex-col
                                       justify-between gap-5
                                       sm:flex-row
                                       sm:items-center"
                            >

                                <div>

                                    <div
                                        class="text-lg font-semibold
                                               text-zinc-900
                                               dark:text-white"
                                    >
                                        {{ $nextActivity->title }}
                                    </div>

                                    <div
                                        class="mt-3 space-y-2
                                               text-sm
                                               text-zinc-600
                                               dark:text-zinc-300"
                                    >

                                        <div
                                            class="flex
                                                   items-center
                                                   gap-2"
                                        >
                                            <flux:icon.calendar
                                                class="size-4
                                                       text-zinc-400"
                                            />

                                            {{
                                                $nextActivity
                                                    ->start_at
                                                    ->locale('id')
                                                    ->translatedFormat(
                                                        'l, d F Y'
                                                    )
                                            }}
                                        </div>

                                        <div
                                            class="flex
                                                   items-center
                                                   gap-2"
                                        >
                                            <flux:icon.clock
                                                class="size-4
                                                       text-zinc-400"
                                            />

                                            {{
                                                $nextActivity
                                                    ->start_at
                                                    ->format('H:i')
                                            }}

                                            @if (
                                                $nextActivity
                                                    ->end_at
                                            )
                                                -
                                                {{
                                                    $nextActivity
                                                        ->end_at
                                                        ->format('H:i')
                                                }}
                                            @endif

                                            WITA
                                        </div>

                                        @if (
                                            filled(
                                                $nextActivity->location
                                            )
                                        )
                                            <div
                                                class="flex
                                                       items-center
                                                       gap-2"
                                            >
                                                <flux:icon.map-pin
                                                    class="size-4
                                                           text-zinc-400"
                                                />

                                                {{
                                                    $nextActivity
                                                        ->location
                                                }}
                                            </div>
                                        @endif

                                    </div>

                                </div>

                                <a
                                    href="{{ route(
                                        'attendances.self'
                                    ) }}"
                                    wire:navigate
                                    class="inline-flex
                                           shrink-0 items-center
                                           justify-center
                                           rounded-lg
                                           bg-emerald-600
                                           px-4 py-2.5
                                           text-sm font-medium
                                           text-white
                                           transition
                                           hover:bg-emerald-700"
                                >
                                    Lihat Absensi
                                </a>

                            </div>

                        </div>

                    @else

                        <div
                            class="py-10 text-center"
                        >
                            <div
                                class="mx-auto flex size-12
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

                            <p
                                class="mt-3 text-sm font-medium
                                       text-zinc-700
                                       dark:text-zinc-300"
                            >
                                Belum ada kegiatan mendatang
                            </p>

                            <p
                                class="mt-1 text-sm
                                       text-zinc-500"
                            >
                                Agenda berikutnya akan muncul
                                di sini setelah dipublikasikan.
                            </p>
                        </div>

                    @endif

                </div>

            </section>


            {{-- AKSI CEPAT --}}
            <section
                class="rounded-2xl border
                       border-zinc-200 bg-white
                       p-5 dark:border-zinc-800
                       dark:bg-zinc-900"
            >

                <h2
                    class="font-semibold text-zinc-900
                           dark:text-white"
                >
                    Akses Cepat
                </h2>

                <p
                    class="mt-1 text-sm text-zinc-500
                           dark:text-zinc-400"
                >
                    Menu yang paling sering digunakan.
                </p>

                <div class="mt-5 space-y-3">

                    @can('attendances.self')
                        <a
                            href="{{ route(
                                'attendances.self'
                            ) }}"
                            wire:navigate
                            class="flex items-center
                                   gap-3 rounded-xl
                                   border border-zinc-200
                                   p-3.5 transition
                                   hover:bg-zinc-50
                                   dark:border-zinc-700
                                   dark:hover:bg-zinc-800"
                        >
                            <div
                                class="flex size-10
                                       items-center
                                       justify-center
                                       rounded-lg
                                       bg-emerald-50
                                       text-emerald-600
                                       dark:bg-emerald-950/50
                                       dark:text-emerald-400"
                            >
                                <flux:icon.map-pin
                                    class="size-5"
                                />
                            </div>

                            <div>
                                <div
                                    class="text-sm
                                           font-medium"
                                >
                                    Absensi Saya
                                </div>

                                <div
                                    class="text-xs
                                           text-zinc-500"
                                >
                                    Lakukan dan lihat absensi
                                </div>
                            </div>
                        </a>
                    @endcan


                    <a
                        href="{{ route(
                            'announcements.my'
                        ) }}"
                        wire:navigate
                        class="flex items-center
                               gap-3 rounded-xl
                               border border-zinc-200
                               p-3.5 transition
                               hover:bg-zinc-50
                               dark:border-zinc-700
                               dark:hover:bg-zinc-800"
                    >
                        <div
                            class="flex size-10
                                   items-center
                                   justify-center
                                   rounded-lg
                                   bg-blue-50
                                   text-blue-600
                                   dark:bg-blue-950/50
                                   dark:text-blue-400"
                        >
                            <flux:icon.megaphone
                                class="size-5"
                            />
                        </div>

                        <div>
                            <div
                                class="text-sm
                                       font-medium"
                            >
                                Pengumuman
                            </div>

                            <div
                                class="text-xs
                                       text-zinc-500"
                            >
                                Informasi dari sekolah
                            </div>
                        </div>
                    </a>


                    <a
                        href="{{ route(
                            'student.grades'
                        ) }}"
                        wire:navigate
                        class="flex items-center
                            gap-3 rounded-xl
                            border border-zinc-200
                            p-3.5 transition
                            hover:bg-zinc-50
                            dark:border-zinc-700
                            dark:hover:bg-zinc-800"
                    >
                        <div
                            class="flex size-10
                                items-center
                                justify-center
                                rounded-lg
                                bg-amber-50
                                text-amber-600
                                dark:bg-amber-950/50
                                dark:text-amber-400"
                        >
                            <flux:icon.academic-cap
                                class="size-5"
                            />
                        </div>

                        <div>
                            <div
                                class="text-sm font-medium"
                            >
                                Nilai Saya
                            </div>

                            <div
                                class="text-xs text-zinc-500"
                            >
                                Lihat hasil penilaian
                            </div>
                        </div>
                    </a>


                    <a
                        href="{{ route(
                            'student.scout-profile'
                        ) }}"
                        wire:navigate
                        class="flex items-center
                            gap-3 rounded-xl
                            border border-zinc-200
                            p-3.5 transition
                            hover:bg-zinc-50
                            dark:border-zinc-700
                            dark:hover:bg-zinc-800"
                    >
                        <div
                            class="flex size-10
                                items-center
                                justify-center
                                rounded-lg
                                bg-violet-50
                                text-violet-600
                                dark:bg-violet-950/50
                                dark:text-violet-400"
                        >
                            <flux:icon.identification
                                class="size-5"
                            />
                        </div>

                        <div>
                            <div class="text-sm font-medium">
                                Profil Pramuka
                            </div>

                            <div class="text-xs text-zinc-500">
                                Golongan dan Regu/Barung
                            </div>
                        </div>
                    </a>


                    <a
                        href="{{ route(
                            'student.skills'
                        ) }}"
                        wire:navigate
                        class="flex items-center
                            gap-3 rounded-xl
                            border border-zinc-200
                            p-3.5 transition
                            hover:bg-zinc-50
                            dark:border-zinc-700
                            dark:hover:bg-zinc-800"
                    >
                        <div
                            class="flex size-10
                                items-center
                                justify-center
                                rounded-lg
                                bg-cyan-50
                                text-cyan-600
                                dark:bg-cyan-950/50
                                dark:text-cyan-400"
                        >
                            <flux:icon.clipboard-document-check
                                class="size-5"
                            />
                        </div>

                        <div>

                            <div
                                class="text-sm font-medium"
                            >
                                Keterampilan Saya
                            </div>

                            <div
                                class="text-xs
                                    text-zinc-500"
                            >
                                Lihat penilaian kegiatan
                            </div>

                        </div>
                    </a>


                    <a
                        href="{{ route(
                            'student.documents'
                        ) }}"
                        wire:navigate
                        class="flex items-center
                            gap-3 rounded-xl
                            border border-zinc-200
                            p-3.5 transition
                            hover:bg-zinc-50
                            dark:border-zinc-700
                            dark:hover:bg-zinc-800"
                    >
                        <div
                            class="flex size-10
                                items-center
                                justify-center
                                rounded-lg
                                bg-sky-50
                                text-sky-600
                                dark:bg-sky-950/50
                                dark:text-sky-400"
                        >
                            <flux:icon.document-text
                                class="size-5"
                            />
                        </div>

                        <div class="min-w-0 flex-1">

                            <div
                                class="flex items-center
                                    justify-between
                                    gap-3"
                            >

                                <div
                                    class="text-sm
                                        font-medium"
                                >
                                    Dokumen Saya
                                </div>

                                @if ($documentCount > 0)

                                    <span
                                        class="inline-flex
                                            min-w-5
                                            items-center
                                            justify-center
                                            rounded-full
                                            bg-sky-100
                                            px-1.5 py-0.5
                                            text-xs
                                            font-semibold
                                            text-sky-700
                                            dark:bg-sky-950
                                            dark:text-sky-300"
                                    >
                                        {{ $documentCount }}
                                    </span>

                                @endif

                            </div>

                            <div
                                class="text-xs
                                    text-zinc-500"
                            >
                                Dokumen resmi untuk Anda
                            </div>

                        </div>
                    </a>


                    <a
                        href="{{ route(
                            'notification-settings.manage'
                        ) }}"
                        wire:navigate
                        class="flex items-center
                               gap-3 rounded-xl
                               border border-zinc-200
                               p-3.5 transition
                               hover:bg-zinc-50
                               dark:border-zinc-700
                               dark:hover:bg-zinc-800"
                    >
                        <div
                            class="flex size-10
                                   items-center
                                   justify-center
                                   rounded-lg
                                   bg-violet-50
                                   text-violet-600
                                   dark:bg-violet-950/50
                                   dark:text-violet-400"
                        >
                            <flux:icon.bell
                                class="size-5"
                            />
                        </div>

                        <div>
                            <div
                                class="text-sm
                                       font-medium"
                            >
                                Notifikasi
                            </div>

                            <div
                                class="text-xs
                                       text-zinc-500"
                            >
                                Atur saluran notifikasi
                            </div>
                        </div>
                    </a>

                </div>

            </section>

        </div>


        {{-- ================================================
        PENGUMUMAN TERBARU
        ================================================= --}}

        @can('announcements.my')

            <section
                class="rounded-2xl border
                    border-zinc-200 bg-white
                    dark:border-zinc-800
                    dark:bg-zinc-900"
            >

                <div
                    class="flex items-center
                        justify-between
                        border-b border-zinc-100
                        px-5 py-4
                        dark:border-zinc-800"
                >
                    <div>
                        <h2
                            class="font-semibold
                                text-zinc-900
                                dark:text-white"
                        >
                            Pengumuman Terbaru
                        </h2>

                        <p
                            class="mt-0.5 text-sm
                                text-zinc-500
                                dark:text-zinc-400"
                        >
                            Informasi terbaru untuk Anda.
                        </p>
                    </div>

                    <a
                        href="{{ route(
                            'announcements.my'
                        ) }}"
                        wire:navigate
                        class="text-sm font-medium
                            text-emerald-600
                            hover:text-emerald-700
                            dark:text-emerald-400"
                    >
                        Lihat semua
                    </a>
                </div>


                <div
                    class="divide-y
                        divide-zinc-100
                        dark:divide-zinc-800"
                >

                    @forelse (
                        $latestAnnouncements
                        as $announcement
                    )

                        <article
                            class="px-5 py-4"
                        >
                            <div
                                class="flex items-start
                                    gap-3"
                            >

                                <div
                                    class="flex size-10
                                        shrink-0
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


                                <div class="min-w-0 flex-1">

                                    <div
                                        class="font-medium
                                            text-zinc-900
                                            dark:text-white"
                                    >
                                        {{ $announcement->title }}
                                    </div>


                                    <div
                                        class="mt-1
                                            line-clamp-2
                                            text-sm
                                            leading-6
                                            text-zinc-500
                                            dark:text-zinc-400"
                                    >
                                        {{
                                            \Illuminate\Support\Str::limit(
                                                strip_tags(
                                                    $announcement->body
                                                ),
                                                180
                                            )
                                        }}
                                    </div>


                                    <div
                                        class="mt-2
                                            text-xs
                                            text-zinc-400"
                                    >
                                        {{
                                            $announcement
                                                ->published_at
                                                ?->format(
                                                    'd M Y H:i'
                                                )
                                        }}
                                    </div>

                                </div>

                            </div>
                        </article>

                    @empty

                        <div
                            class="px-5 py-10
                                text-center"
                        >
                            <div
                                class="mx-auto flex size-11
                                    items-center
                                    justify-center
                                    rounded-xl
                                    bg-zinc-100
                                    text-zinc-400
                                    dark:bg-zinc-800"
                            >
                                <flux:icon.megaphone
                                    class="size-5"
                                />
                            </div>

                            <p
                                class="mt-3 text-sm
                                    text-zinc-500"
                            >
                                Belum ada pengumuman terbaru.
                            </p>
                        </div>

                    @endforelse

                </div>

            </section>

        @endcan


        {{-- ================================================
        RIWAYAT KEHADIRAN
        ================================================= --}}

        <section
            id="riwayat"
            class="rounded-2xl border
                   border-zinc-200 bg-white
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="flex items-center
                       justify-between
                       border-b border-zinc-100
                       px-5 py-4
                       dark:border-zinc-800"
            >
                <div>
                    <h2
                        class="font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        Riwayat Kehadiran
                    </h2>

                    <p
                        class="mt-0.5 text-sm
                               text-zinc-500
                               dark:text-zinc-400"
                    >
                        Lima catatan kehadiran terakhir.
                    </p>
                </div>

                @can('attendances.self')
                    <a
                        href="{{ route(
                            'attendances.self'
                        ) }}"
                        wire:navigate
                        class="text-sm font-medium
                               text-emerald-600
                               hover:text-emerald-700
                               dark:text-emerald-400"
                    >
                        Lihat semua
                    </a>
                @endcan
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">

                @forelse (
                    $recentAttendances as $record
                )

                    @php
                        $status = match (
                            $record->status
                        ) {
                            'present' => [
                                'Hadir',
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                            ],

                            'late' => [
                                'Terlambat',
                                'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                            ],

                            'sick' => [
                                'Sakit',
                                'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300',
                            ],

                            'excused' => [
                                'Izin',
                                'bg-violet-50 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300',
                            ],

                            'absent' => [
                                'Alpa',
                                'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                            ],

                            default => [
                                ucfirst(
                                    $record->status
                                ),
                                'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                            ],
                        };
                    @endphp

                    <div
                        class="flex items-center
                               justify-between gap-4
                               px-5 py-4"
                    >

                        <div
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex size-9
                                       items-center
                                       justify-center
                                       rounded-lg
                                       bg-zinc-100
                                       text-zinc-500
                                       dark:bg-zinc-800
                                       dark:text-zinc-400"
                            >
                                <flux:icon.calendar
                                    class="size-4"
                                />
                            </div>

                            <div>
                                <div
                                    class="text-sm
                                           font-medium
                                           text-zinc-900
                                           dark:text-white"
                                >
                                    {{
                                        $record->created_at
                                            ?->locale('id')
                                            ->translatedFormat(
                                                'd F Y'
                                            )
                                    }}
                                </div>

                                <div
                                    class="mt-0.5 text-xs
                                           text-zinc-500"
                                >
                                    Catatan kehadiran
                                </div>
                            </div>
                        </div>

                        <span
                            class="rounded-full px-2.5
                                   py-1 text-xs
                                   font-medium
                                   {{ $status[1] }}"
                        >
                            {{ $status[0] }}
                        </span>

                    </div>

                @empty

                    <div
                        class="px-5 py-10
                               text-center
                               text-sm text-zinc-500"
                    >
                        Belum ada riwayat kehadiran.
                    </div>

                @endforelse

            </div>

        </section>

    @endif

</div>