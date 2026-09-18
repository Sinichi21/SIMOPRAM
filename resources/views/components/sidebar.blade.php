<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="simpram-app min-h-screen bg-[#f7f5ee] text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">

    @php
        $currentSchoolId = app(\App\Support\SchoolContext::class)->id();
        $currentUser = auth()->user();
        $hasActiveSchool = $currentSchoolId !== null;
        $isStudent = $currentUser ?->hasRole('student') ?? false;
        $isCoach = $currentUser ?->hasRole('coach') ?? false;
        $contentSchool = request()->route('school') instanceof \App\Models\School
            ? request()->route('school')
            : ($activeSchool ?? $schools->firstWhere('id', session('active_school_id')));
    @endphp

    {{-- =====================================================
    SIDEBAR
    ====================================================== --}}

    <flux:sidebar
        sticky
        collapsible="mobile"
        {{-- x-data="{ hasActiveSchool: @js($hasActiveSchool) }" --}}
        x-data="{ hasActiveSchool: {{ $hasActiveSchool ? 'true' : 'false' }} }"
        x-on:click.capture="
            if (! hasActiveSchool && $event.target.closest('[data-school-menu]')) {
                $event.preventDefault();
                $event.stopPropagation();
                $flux.toast({
                    variant: 'warning',
                    heading: 'Sekolah belum dipilih',
                    text: 'Pilih sekolah aktif terlebih dahulu.'
                });
            }
        "
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900"
    >

        {{-- HEADER / LOGO --}}
        <flux:sidebar.header>
            <x-app-logo
                :sidebar="true"
                href="{{ route('dashboard') }}"
                wire:navigate
            />

            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        @if ($isStudent)

            {{-- =================================================
            IDENTITAS SISWA
            ================================================== --}}

            @if ($activeSchool)

                <div class="px-2 pb-2">

                    <div
                        class="rounded-xl border
                            border-zinc-200
                            bg-white px-3 py-3
                            dark:border-zinc-700
                            dark:bg-zinc-800"
                    >

                        <div
                            class="text-[11px] font-medium
                                uppercase tracking-wide
                                text-zinc-400"
                        >
                            Pangkalan
                        </div>

                        <div
                            class="mt-1 truncate
                                text-sm font-semibold
                                text-zinc-900
                                dark:text-white"
                            title="{{ $activeSchool->name }}"
                        >
                            {{ $activeSchool->name }}
                        </div>

                    </div>

                </div>

            @endif


            {{-- =================================================
            BERANDA
            ================================================== --}}

            <flux:sidebar.nav>

                <flux:sidebar.group
                    heading="Beranda"
                    class="grid"
                >

                    <flux:sidebar.item
                        icon="home"
                        :href="route('dashboard')"
                        :current="request()->routeIs(
                            'dashboard'
                        )"
                        wire:navigate
                    >
                        Dashboard
                    </flux:sidebar.item>

                </flux:sidebar.group>

            </flux:sidebar.nav>


            {{-- =================================================
            KEGIATAN SAYA
            ================================================== --}}

            <flux:sidebar.nav>

                <flux:sidebar.group
                    heading="Kegiatan Saya"
                    class="grid"
                >

                    {{-- Untuk sementara agenda berada
                        di dashboard siswa --}}
                    <flux:sidebar.item
                        icon="calendar-days"
                        href="{{ route('dashboard') }}#agenda"
                    >
                        Agenda
                    </flux:sidebar.item>


                    @can('attendances.self')

                        <flux:sidebar.item
                            icon="map-pin"
                            :href="route(
                                'attendances.self'
                            )"
                            :current="request()->routeIs(
                                'attendances.self'
                            )"
                            wire:navigate
                        >
                            Absensi Saya
                        </flux:sidebar.item>

                    @endcan


                    <flux:sidebar.item
                        icon="clock"
                        href="{{ route('dashboard') }}#riwayat"
                    >
                        Riwayat Kehadiran
                    </flux:sidebar.item>

                </flux:sidebar.group>

            </flux:sidebar.nav>


            {{-- =================================================
            PERKEMBANGAN SAYA
            ================================================== --}}

            <flux:sidebar.nav>

                <flux:sidebar.group
                    heading="Perkembangan Saya"
                    class="grid"
                >

                    <flux:sidebar.item
                        icon="academic-cap"
                        :href="route(
                            'student.grades'
                        )"
                        :current="request()->routeIs(
                            'student.grades'
                        )"
                        wire:navigate
                    >
                        Nilai Saya
                    </flux:sidebar.item>


                     <flux:sidebar.item
                        icon="clipboard-document-check"
                        :href="route(
                            'student.skills'
                        )"
                        :current="request()->routeIs(
                            'student.skills'
                        )"
                        wire:navigate
                    >
                        Keterampilan Saya
                    </flux:sidebar.item>


                    <flux:sidebar.item
                        icon="chart-bar"
                        :href="route(
                            'student.grade-history'
                        )"
                        :current="request()->routeIs(
                            'student.grade-history'
                        )"
                        wire:navigate
                    >
                        Riwayat Nilai
                    </flux:sidebar.item>


                    <flux:sidebar.item
                        icon="identification"
                        :href="route(
                            'student.scout-profile'
                        )"
                        :current="request()->routeIs(
                            'student.scout-profile'
                        )"
                        wire:navigate
                    >
                        Profil Pramuka
                    </flux:sidebar.item>

                </flux:sidebar.group>

            </flux:sidebar.nav>


            {{-- =================================================
            INFORMASI
            ================================================== --}}

            <flux:sidebar.nav>

                <flux:sidebar.group
                    heading="Informasi"
                    class="grid"
                >

                    <flux:sidebar.item
                        icon="megaphone"
                        :href="route(
                            'announcements.my'
                        )"
                        :current="request()->routeIs(
                            'announcements.my'
                        )"
                        wire:navigate
                    >
                        Pengumuman
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="document-text"
                        :href="route(
                            'student.documents'
                        )"
                        :current="request()->routeIs(
                            'student.documents'
                        )"
                        wire:navigate
                    >
                        Dokumen Saya
                    </flux:sidebar.item>

                </flux:sidebar.group>

            </flux:sidebar.nav>


            {{-- =================================================
            AKUN
            ================================================== --}}

            <flux:sidebar.nav>

                <flux:sidebar.group
                    heading="Akun"
                    class="grid"
                >

                    <flux:sidebar.item
                        icon="bell-alert"
                        :href="route(
                            'notification-settings.manage'
                        )"
                        :current="request()->routeIs(
                            'notification-settings.*'
                        )"
                        wire:navigate
                    >
                        Pengaturan Notifikasi
                    </flux:sidebar.item>

                </flux:sidebar.group>

            </flux:sidebar.nav>


        @elseif ($isCoach)

        
            {{-- =====================================================
            SEKOLAH AKTIF
            ====================================================== --}}

            <div
                class="mx-2 mb-3
                    rounded-2xl
                    border border-zinc-200
                    bg-white p-3
                    shadow-sm
                    dark:border-zinc-800
                    dark:bg-zinc-900"
            >

                <div
                    class="mb-2 flex
                        items-center gap-2"
                >
                    <div
                        class="flex size-8
                            items-center
                            justify-center
                            rounded-lg
                            bg-emerald-50
                            text-emerald-600
                            dark:bg-emerald-950/50
                            dark:text-emerald-400"
                    >
                        <flux:icon.building-library
                            class="size-4"
                        />
                    </div>

                    <div class="min-w-0">
                        <div
                            class="text-[10px]
                                font-semibold
                                uppercase
                                tracking-wider
                                text-zinc-400"
                        >
                            Sekolah Aktif
                        </div>

                        <div
                            class="text-xs
                                text-zinc-500"
                        >
                            Area Pembina
                        </div>
                    </div>
                </div>


                {{-- Menggunakan LOGIKA SELECTOR EXISTING --}}
                <form
                    method="POST"
                    action="{{
                        route(
                            'school.switch'
                        )
                    }}"
                >
                    @csrf

                    <select
                        name="school_id"
                        onchange="this.form.submit()"
                        class="w-full
                            rounded-xl
                            border border-zinc-200
                            bg-zinc-50
                            px-3 py-2.5
                            text-sm
                            font-medium
                            text-zinc-800
                            outline-none
                            transition
                            focus:border-emerald-500
                            focus:ring-2
                            focus:ring-emerald-500/10
                            dark:border-zinc-700
                            dark:bg-zinc-800
                            dark:text-zinc-100"
                    >

                        @foreach (
                            $schools
                            as $school
                        )

                            <option
                                value="{{ $school->id }}"
                                @selected(
                                    $currentSchoolId
                                    ===
                                    $school->id
                                )
                            >
                                {{ $school->name }}
                            </option>

                        @endforeach

                    </select>

                </form>

            </div>


            {{-- =====================================================
            BERANDA
            ====================================================== --}}

            <flux:sidebar.nav>

                <flux:sidebar.group
                    heading="Beranda"
                    class="grid"
                >

                    <flux:sidebar.item
                        icon="home"
                        :href="route('dashboard')"
                        :current="
                            request()->routeIs(
                                'dashboard'
                            )
                        "
                        wire:navigate
                    >
                        Dashboard
                    </flux:sidebar.item>

                </flux:sidebar.group>

            </flux:sidebar.nav>


            {{-- =====================================================
            KEGIATAN
            ====================================================== --}}

            @canany([
                'activities.view',
                'attendance_sessions.view',
                'journals.view'
            ])

                <flux:sidebar.nav
                    data-school-menu
                    @class([
                        'opacity-60 [&_*]:cursor-not-allowed'
                            => ! $hasActiveSchool,
                    ])
                >

                    <flux:sidebar.group
                        heading="Kegiatan"
                        expandable
                        :expanded="
                            request()->routeIs(
                                'activities.*',
                                'attendances.*',
                                'journals.*'
                            )
                        "
                        class="grid"
                    >

                        @can('activities.view')

                            <flux:sidebar.item
                                icon="calendar-days"
                                :href="route(
                                    'activities.index'
                                )"
                                :current="
                                    request()->routeIs(
                                        'activities.*'
                                    )
                                "
                                wire:navigate
                            >
                                Agenda & Kegiatan
                            </flux:sidebar.item>

                        @endcan


                        @can('attendance_sessions.view')

                            <flux:sidebar.item
                                icon="clipboard-document-check"
                                :href="route(
                                    'attendances.index'
                                )"
                                :current="
                                    request()->routeIs(
                                        'attendances.index',
                                        'attendances.manage'
                                    )
                                "
                                wire:navigate
                            >
                                Absensi
                            </flux:sidebar.item>

                        @endcan


                        @can('journals.view')

                            <flux:sidebar.item
                                icon="document-text"
                                :href="route(
                                    'journals.index'
                                )"
                                :current="
                                    request()->routeIs(
                                        'journals.*'
                                    )
                                "
                                wire:navigate
                            >
                                Jurnal Kegiatan
                            </flux:sidebar.item>

                        @endcan

                    </flux:sidebar.group>

                </flux:sidebar.nav>

            @endcanany


            {{-- =====================================================
            PENILAIAN
            ====================================================== --}}

            @canany([
                'assessments.scores.view',
                'activity_assessments.view',
                'assessment_sync.view',
                // 'assessment_audit.view',
                'semester_closures.view'
            ])

                <flux:sidebar.nav
                    data-school-menu
                    @class([
                        'opacity-60 [&_*]:cursor-not-allowed'
                            => ! $hasActiveSchool,
                    ])
                >

                    <flux:sidebar.group
                        heading="Penilaian"
                        expandable
                        :expanded="
                            request()->routeIs(
                                'assessments.scores',
                                'activity-assessments.*'
                            )
                        "
                        class="grid"
                    >

                        @can(
                            'assessments.scores.view'
                        )

                            <flux:sidebar.item
                                icon="pencil-square"
                                :href="route(
                                    'assessments.scores'
                                )"
                                :current="
                                    request()->routeIs(
                                        'assessments.scores'
                                    )
                                "
                                wire:navigate
                            >
                                Input Nilai
                            </flux:sidebar.item>

                        @endcan


                        @can(
                            'activity_assessments.view'
                        )

                            <flux:sidebar.item
                                icon="academic-cap"
                                :href="route(
                                    'activity-assessments.index'
                                )"
                                :current="
                                    request()->routeIs(
                                        'activity-assessments.*'
                                    )
                                "
                                wire:navigate
                            >
                                Penilaian Kegiatan
                            </flux:sidebar.item>

                        @endcan

                        @can('assessment_sync.view')
                            <flux:sidebar.item
                                icon="arrow-path"
                                :href="route('assessment-sync.index')"
                                :current="request()->routeIs('assessment-sync.*')"
                                wire:navigate
                            >
                                Sinkronisasi Penilaian
                            </flux:sidebar.item>
                        @endcan

                        {{-- @can('assessment_audit.view')
                            <flux:sidebar.item
                                icon="document-magnifying-glass"
                                :href="route('assessment-audit.index')"
                                :current="request()->routeIs('assessment-audit.*')"
                                wire:navigate
                            >
                                Audit Penilaian
                            </flux:sidebar.item>
                        @endcan --}}

                        @can('semester_closures.view')
                            <flux:sidebar.item
                                icon="lock-closed"
                                :href="route('semester-closures.index')"
                                :current="request()->routeIs('semester-closures.*')"
                                wire:navigate
                            >
                                Kunci Semester
                            </flux:sidebar.item>
                        @endcan

                        @can('assessments.view')
                            <flux:sidebar.item
                                data-school-menu
                                @class([
                                    'opacity-60 cursor-not-allowed' => ! $hasActiveSchool,
                                ])
                                icon="clipboard-document-check"
                                :href="route('assessments.settings')"
                                :current="request()->routeIs('assessments.settings')"
                                wire:navigate
                            >
                                Pengaturan Penilaian
                            </flux:sidebar.item>
                        @endcan

                    </flux:sidebar.group>

                </flux:sidebar.nav>

            @endcanany


            {{-- =====================================================
            DATA BINAAN
            ====================================================== --}}

            @canany([
                'students.view',
                'scout_units.view'
            ])

                <flux:sidebar.nav
                    data-school-menu
                    @class([
                        'opacity-60 [&_*]:cursor-not-allowed'
                            => ! $hasActiveSchool,
                    ])
                >

                    <flux:sidebar.group
                        heading="Data Binaan"
                        expandable
                        :expanded="
                            request()->routeIs(
                                'students.*',
                                'scout-units.*'
                            )
                        "
                        class="grid"
                    >

                        @can('students.view')

                            <flux:sidebar.item
                                icon="users"
                                :href="route(
                                    'students.index'
                                )"
                                :current="
                                    request()->routeIs(
                                        'students.*'
                                    )
                                "
                                wire:navigate
                            >
                                Siswa
                            </flux:sidebar.item>

                        @endcan


                        @can('scout_units.view')

                            <flux:sidebar.item
                                icon="rectangle-group"
                                :href="route(
                                    'scout-units.index'
                                )"
                                :current="
                                    request()->routeIs(
                                        'scout-units.*'
                                    )
                                "
                                wire:navigate
                            >
                                Regu / Barung
                            </flux:sidebar.item>

                        @endcan

                    </flux:sidebar.group>

                </flux:sidebar.nav>

            @endcanany


            {{-- =====================================================
            LAPORAN
            ====================================================== --}}

            @canany([
                'reports.grades.view',
                'reports.attendance.view',
                'reports.lpj.view'
            ])

                <flux:sidebar.nav
                    data-school-menu
                    @class([
                        'opacity-60 [&_*]:cursor-not-allowed'
                            => ! $hasActiveSchool,
                    ])
                >

                    <flux:sidebar.group
                        heading="Laporan"
                        expandable
                        :expanded="
                            request()->routeIs(
                                'reports.grades',
                                'reports.attendance',
                                'reports.lpj*'
                            )
                        "
                        class="grid"
                    >

                        @can(
                            'reports.grades.view'
                        )

                            <flux:sidebar.item
                                icon="academic-cap"
                                :href="route(
                                    'reports.grades'
                                )"
                                :current="
                                    request()->routeIs(
                                        'reports.grades'
                                    )
                                "
                                wire:navigate
                            >
                                Rekap Nilai
                            </flux:sidebar.item>

                        @endcan


                        @can(
                            'reports.attendance.view'
                        )

                            <flux:sidebar.item
                                icon="clipboard-document-list"
                                :href="route(
                                    'reports.attendance'
                                )"
                                :current="
                                    request()->routeIs(
                                        'reports.attendance'
                                    )
                                "
                                wire:navigate
                            >
                                Rekap Absensi
                            </flux:sidebar.item>

                        @endcan


                        @can('reports.lpj.view')

                            <flux:sidebar.item
                                icon="document-text"
                                :href="route(
                                    'reports.lpj'
                                )"
                                :current="
                                    request()->routeIs(
                                        'reports.lpj*'
                                    )
                                "
                                wire:navigate
                            >
                                LPJ Kegiatan
                            </flux:sidebar.item>

                        @endcan

                    </flux:sidebar.group>

                </flux:sidebar.nav>

            @endcanany


            {{-- =====================================================
            INFORMASI
            ====================================================== --}}

            <flux:sidebar.nav
                data-school-menu
                @class([
                    'opacity-60 [&_*]:cursor-not-allowed'
                        => ! $hasActiveSchool,
                ])
            >

                <flux:sidebar.group
                    heading="Informasi"
                    expandable
                    :expanded="
                        request()->routeIs(
                            'announcements.*'
                        )
                    "
                    class="grid"
                >

                    @can('announcements.view')

                        <flux:sidebar.item
                            icon="megaphone"
                            :href="route(
                                'announcements.index'
                            )"
                            :current="
                                request()->routeIs(
                                    'announcements.index'
                                )
                            "
                            wire:navigate
                        >
                            Kelola Pengumuman
                        </flux:sidebar.item>

                    @endcan


                    <flux:sidebar.item
                        icon="bell"
                        :href="route(
                            'announcements.my'
                        )"
                        :current="
                            request()->routeIs(
                                'announcements.my'
                            )
                        "
                        wire:navigate
                    >
                        Pengumuman Saya
                    </flux:sidebar.item>

                </flux:sidebar.group>

            </flux:sidebar.nav>


            {{-- =====================================================
            AKUN
            ====================================================== --}}

            <flux:sidebar.nav>

                <flux:sidebar.group
                    heading="Akun"
                    class="grid"
                >

                    <flux:sidebar.item
                        icon="bell"
                        :href="route(
                            'notification-settings.manage'
                        )"
                        :current="
                            request()->routeIs(
                                'notification-settings.*'
                            )
                        "
                        wire:navigate
                    >
                        Pengaturan Notifikasi
                    </flux:sidebar.item>

                </flux:sidebar.group>

            </flux:sidebar.nav>


        @else

            {{-- =================================================
            MENU UTAMA
            ================================================== --}}

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Menu Utama')" class="grid">
                    <flux:sidebar.item
                        icon="home"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        wire:navigate
                    >
                        Dashboard
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="chat-bubble-left-right"
                        :href="route('complaints.index')"
                        :current="request()->routeIs('complaints.index', 'complaints.show')"
                        wire:navigate
                    >
                        Pengaduan
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            {{-- =================================================
            SEKOLAH AKTIF
            Diletakkan di atas seluruh menu yang bergantung pada sekolah.
            ================================================== --}}

            @if ($currentUser && app(\App\Services\GlobalActivityAccess::class)->canEnter($currentUser))
                <flux:sidebar.group heading="Informasi Umum" class="grid">
                    @if($currentUser->isSuperAdmin())<flux:sidebar.item :href="route('admin.public-announcements')" :current="request()->routeIs('admin.public-announcements')" wire:navigate>Pengumuman Umum</flux:sidebar.item>@endif
                    <flux:sidebar.item :href="route('admin.public-activities')" :current="request()->routeIs('admin.public-activities')" wire:navigate>Kegiatan Umum</flux:sidebar.item>
                    <flux:sidebar.item :href="route('admin.public-assessments')" :current="request()->routeIs('admin.public-assessments')" wire:navigate>Penilaian Umum</flux:sidebar.item>
                </flux:sidebar.group>
            @endif
            <flux:sidebar.item :href="route('activity-access.mine')" :current="request()->routeIs('activity-access.mine')">Kegiatan Umum Saya</flux:sidebar.item>
            <form
                method="POST"
                action="{{ route('school.switch') }}"
                class="px-2"
            >
                @csrf

                <label class="mb-1 block text-xs font-medium text-zinc-500">
                    Sekolah Aktif
                </label>

                <select
                    name="school_id"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                >
                    @if ($currentUser && $currentUser->isSuperAdmin())
                        <option
                            value="global"
                            @selected($currentSchoolId === null)
                        >
                            🌐 Semua Sekolah / Global
                        </option>
                    @endif

                    @foreach ($schools as $school)
                        <option
                            value="{{ $school->id }}"
                            @selected($currentSchoolId === $school->id)
                        >
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if ($activeSchool)
                <div
                    class="mx-2 mt-3 rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                >
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                        Sedang mengelola
                    </div>

                    <div
                        class="mt-1 truncate text-sm font-semibold text-zinc-900 dark:text-white"
                        title="{{ $activeSchool->name }}"
                    >
                        {{ $activeSchool->name }}
                    </div>
                </div>
            @endif

            {{-- =================================================
            KEGIATAN
            Alur kerja harian ditempatkan paling atas setelah sekolah aktif.
            ================================================== --}}

            @canany(['activities.view', 'attendance_sessions.view', 'journals.view', 'announcements.view'])
                <flux:sidebar.nav
                    data-school-menu
                    @class([
                        'opacity-60 [&_*]:cursor-not-allowed' => ! $hasActiveSchool,
                    ])
                >
                    <flux:sidebar.group
                        heading="Kegiatan"
                        expandable
                        :expanded="request()->routeIs(
                            'activities.*',
                            'attendances.index',
                            'journals.*',
                            'announcements.index',
                            'announcements.create',
                            'announcements.edit'
                        )"
                        class="grid"
                    >
                        @can('activities.view')
                            <flux:sidebar.item
                                icon="calendar-days"
                                :href="route('activities.index')"
                                :current="request()->routeIs('activities.*')"
                                wire:navigate
                            >
                                Agenda / Kegiatan
                            </flux:sidebar.item>
                        @endcan

                        @can('attendance_sessions.view')
                            <flux:sidebar.item
                                icon="clipboard-document-check"
                                :href="route('attendances.index')"
                                :current="request()->routeIs('attendances.index')"
                                wire:navigate
                            >
                                Absensi
                            </flux:sidebar.item>
                        @endcan

                        @can('journals.view')
                            <flux:sidebar.item
                                icon="document-text"
                                :href="route('journals.index')"
                                :current="request()->routeIs('journals.*')"
                                wire:navigate
                            >
                                Jurnal Kegiatan
                            </flux:sidebar.item>
                        @endcan

                        @can('announcements.view')
                            <flux:sidebar.item
                                icon="megaphone"
                                :href="route('announcements.index')"
                                :current="request()->routeIs(
                                    'announcements.index',
                                    'announcements.create',
                                    'announcements.edit'
                                )"
                                wire:navigate
                            >
                                Pengumuman
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcanany

            {{-- =================================================
            PENILAIAN
            Pengaturan Penilaian dipindahkan ke kelompok Pengaturan.
            ================================================== --}}

            @canany([
                'assessments.scores.view',
                'activity_assessments.view',
                'assessment_sync.view',
                'assessment_audit.view',
                'semester_closures.view'
            ])
                <flux:sidebar.nav
                    data-school-menu
                    @class([
                        'opacity-60 [&_*]:cursor-not-allowed' => ! $hasActiveSchool,
                    ])
                >
                    <flux:sidebar.group
                        heading="Penilaian"
                        expandable
                        :expanded="request()->routeIs(
                            'assessments.scores',
                            'activity-assessments.*',
                            'assessment-sync.*',
                            'assessment-audit.*',
                            'semester-closures.*'
                        )"
                        class="grid"
                    >
                        @can('assessments.scores.view')
                            <flux:sidebar.item
                                icon="pencil-square"
                                :href="route('assessments.scores')"
                                :current="request()->routeIs('assessments.scores')"
                                wire:navigate
                            >
                                Input Nilai
                            </flux:sidebar.item>
                        @endcan

                        @can('activity_assessments.view')
                            <flux:sidebar.item
                                icon="clipboard-document-check"
                                :href="route('activity-assessments.index')"
                                :current="request()->routeIs('activity-assessments.*')"
                                wire:navigate
                            >
                                Penilaian Kegiatan
                            </flux:sidebar.item>
                        @endcan

                        @can('assessment_sync.view')
                            <flux:sidebar.item
                                icon="arrow-path"
                                :href="route('assessment-sync.index')"
                                :current="request()->routeIs('assessment-sync.*')"
                                wire:navigate
                            >
                                Sinkronisasi Penilaian
                            </flux:sidebar.item>
                        @endcan

                        @can('assessment_audit.view')
                            <flux:sidebar.item
                                icon="document-magnifying-glass"
                                :href="route('assessment-audit.index')"
                                :current="request()->routeIs('assessment-audit.*')"
                                wire:navigate
                            >
                                Audit Penilaian
                            </flux:sidebar.item>
                        @endcan

                        @can('semester_closures.view')
                            <flux:sidebar.item
                                icon="lock-closed"
                                :href="route('semester-closures.index')"
                                :current="request()->routeIs('semester-closures.*')"
                                wire:navigate
                            >
                                Kunci Semester
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcanany

            {{-- =================================================
            DATA SEKOLAH
            Sebelumnya bernama Master Data.
            ================================================== --}}

            @canany([
                'academic_years.view',
                'semesters.view',
                'classrooms.view',
                'students.view',
                'coaches.view',
                'gudep.view',
                'scout_units.view'
            ])
                <flux:sidebar.nav
                    data-school-menu
                    @class([
                        'opacity-60 [&_*]:cursor-not-allowed' => ! $hasActiveSchool,
                    ])
                >
                    <flux:sidebar.group
                        heading="Data Sekolah"
                        expandable
                        :expanded="request()->routeIs(
                            'academic-years.*',
                            'semesters.*',
                            'classrooms.*',
                            'students.*',
                            'coaches.*',
                            'scout-groups.*',
                            'scout-units.*'
                        )"
                        class="grid"
                    >
                        @can('academic_years.view')
                            <flux:sidebar.item
                                icon="calendar-days"
                                :href="route('academic-years.index')"
                                :current="request()->routeIs('academic-years.*')"
                                wire:navigate
                            >
                                Tahun Ajaran
                            </flux:sidebar.item>
                        @endcan

                        @can('semesters.view')
                            <flux:sidebar.item
                                icon="calendar"
                                :href="route('semesters.index')"
                                :current="request()->routeIs('semesters.*')"
                                wire:navigate
                            >
                                Semester
                            </flux:sidebar.item>
                        @endcan

                        @can('classrooms.view')
                            <flux:sidebar.item
                                icon="academic-cap"
                                :href="route('classrooms.index')"
                                :current="request()->routeIs('classrooms.*')"
                                wire:navigate
                            >
                                Kelas
                            </flux:sidebar.item>
                        @endcan

                        @can('students.view')
                            <flux:sidebar.item
                                icon="users"
                                :href="route('students.index')"
                                :current="request()->routeIs('students.*')"
                                wire:navigate
                            >
                                Siswa
                            </flux:sidebar.item>
                        @endcan

                        @can('coaches.view')
                            <flux:sidebar.item
                                icon="user-group"
                                :href="route('coaches.index')"
                                :current="request()->routeIs('coaches.*')"
                                wire:navigate
                            >
                                Pembina
                            </flux:sidebar.item>
                        @endcan

                        @can('gudep.view')
                            <flux:sidebar.item
                                icon="flag"
                                :href="route('scout-groups.index')"
                                :current="request()->routeIs('scout-groups.*')"
                                wire:navigate
                            >
                                Gugus Depan
                            </flux:sidebar.item>
                        @endcan

                        @can('scout_units.view')
                            <flux:sidebar.item
                                icon="rectangle-group"
                                :href="route('scout-units.index')"
                                :current="request()->routeIs('scout-units.*')"
                                wire:navigate
                            >
                                Regu / Barung
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcanany

            {{-- =================================================
            PERSURATAN
            Pengaturan Persuratan dipindahkan ke kelompok Pengaturan.
            ================================================== --}}

            @canany(['letters.view', 'report_verifications.view', 'letters.templates'])
                <flux:sidebar.nav
                    data-school-menu
                    @class([
                        'opacity-60 [&_*]:cursor-not-allowed' => ! $hasActiveSchool,
                    ])
                >
                    <flux:sidebar.group
                        heading="Persuratan"
                        expandable
                        :expanded="(
                            request()->routeIs('letters.*')
                            && ! request()->routeIs('letters.settings')
                        ) || request()->routeIs('reports.published-documents.*')"
                        class="grid"
                    >
                        @can('letters.view')
                            <flux:sidebar.item
                                icon="inbox-arrow-down"
                                :href="route('letters.incoming')"
                                :current="request()->routeIs('letters.incoming')"
                                wire:navigate
                            >
                                Surat Masuk
                            </flux:sidebar.item>

                            <flux:sidebar.item
                                icon="paper-airplane"
                                :href="route('letters.outgoing')"
                                :current="request()->routeIs('letters.outgoing')"
                                wire:navigate
                            >
                                Surat Keluar
                            </flux:sidebar.item>
                        @endcan

                        @can('report_verifications.view')
                            <flux:sidebar.item
                                icon="document-check"
                                :href="route('reports.published-documents.index')"
                                :current="request()->routeIs('reports.published-documents.*')"
                                wire:navigate
                            >
                                Dokumen Terbit
                            </flux:sidebar.item>
                        @endcan

                        @can('letters.templates')
                            <flux:sidebar.item
                                icon="document-text"
                                :href="route('letters.templates')"
                                :current="request()->routeIs('letters.templates')"
                                wire:navigate
                            >
                                Template Surat
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcanany

            {{-- =================================================
            LAPORAN
            Wrapper permission berdiri sendiri agar tidak bergantung pada Kegiatan.
            ================================================== --}}

            @canany(['reports.grades.view', 'reports.attendance.view', 'reports.lpj.view'])
                <flux:sidebar.nav
                    data-school-menu
                    @class([
                        'opacity-60 [&_*]:cursor-not-allowed' => ! $hasActiveSchool,
                    ])
                >
                    <flux:sidebar.group
                        heading="Laporan"
                        expandable
                        :expanded="request()->routeIs(
                            'reports.grades',
                            'reports.attendance',
                            'reports.lpj*'
                        )"
                        class="grid"
                    >
                        @can('reports.grades.view')
                            <flux:sidebar.item
                                icon="academic-cap"
                                :href="route('reports.grades')"
                                :current="request()->routeIs('reports.grades')"
                                wire:navigate
                            >
                                Rekap Nilai
                            </flux:sidebar.item>
                        @endcan

                        @can('reports.attendance.view')
                            <flux:sidebar.item
                                icon="clipboard-document-list"
                                :href="route('reports.attendance')"
                                :current="request()->routeIs('reports.attendance')"
                                wire:navigate
                            >
                                Rekap Absensi
                            </flux:sidebar.item>
                        @endcan

                        @can('reports.lpj.view')
                            <flux:sidebar.item
                                icon="document-text"
                                :href="route('reports.lpj')"
                                :current="request()->routeIs('reports.lpj*')"
                                wire:navigate
                            >
                                LPJ Kegiatan
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcanany

            {{-- =================================================
            MANAJEMEN PENGGUNA
            ================================================== --}}

            @canany(['user_approvals.manage', 'coach_accounts.manage', 'student_accounts.manage'])
                <flux:sidebar.nav
                    data-school-menu
                    @class([
                        'opacity-60 [&_*]:cursor-not-allowed' => ! $hasActiveSchool,
                    ])
                >
                    <flux:sidebar.group
                        heading="Manajemen Pengguna"
                        expandable
                        :expanded="request()->routeIs(
                            'user-approvals.*',
                            'coach-accounts.*',
                            'student-accounts.*'
                        )"
                        class="grid"
                    >
                        @can('user_approvals.manage')
                            <flux:sidebar.item
                                icon="users"
                                :href="route('user-approvals.index')"
                                :current="request()->routeIs('user-approvals.*')"
                                wire:navigate
                            >
                                Akun dan Persetujuan
                            </flux:sidebar.item>
                        @endcan

                        @can('coach_accounts.manage')
                            <flux:sidebar.item
                                icon="user-group"
                                :href="route('coach-accounts.index')"
                                :current="request()->routeIs('coach-accounts.*')"
                                wire:navigate
                            >
                                Akun Pembina
                            </flux:sidebar.item>
                        @endcan

                        @can('student_accounts.manage')
                            <flux:sidebar.item
                                icon="users"
                                :href="route('student-accounts.index')"
                                :current="request()->routeIs('student-accounts.*')"
                                wire:navigate
                            >
                                Akun Siswa
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcanany

            {{-- =================================================
            INFORMASI SAYA
            Dipisahkan dari Kegiatan supaya menu personal tidak bergantung
            pada permission modul pengelolaan kegiatan.
            ================================================== --}}

            <flux:sidebar.nav
                data-school-menu
                @class([
                    'opacity-60 [&_*]:cursor-not-allowed' => ! $hasActiveSchool,
                ])
            >
                <flux:sidebar.group
                    heading="Informasi Saya"
                    expandable
                    :expanded="request()->routeIs(
                        'attendances.self',
                        'announcements.my'
                    )"
                    class="grid"
                >
                    @can('attendances.self')
                        <flux:sidebar.item
                            icon="map-pin"
                            :href="route('attendances.self')"
                            :current="request()->routeIs('attendances.self')"
                            wire:navigate
                        >
                            Absensi Saya
                        </flux:sidebar.item>
                    @endcan

                    <flux:sidebar.item
                        icon="bell"
                        :href="route('announcements.my')"
                        :current="request()->routeIs('announcements.my')"
                        wire:navigate
                    >
                        Pengumuman Saya
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            {{-- =================================================
            ADMINISTRASI SISTEM
            Diletakkan mendekati bagian bawah karena bukan aktivitas harian.
            ================================================== --}}

            @can('schools.manage')
                <flux:sidebar.nav>
                    <flux:sidebar.group
                        heading="Administrasi Sistem"
                        expandable
                        :expanded="request()->routeIs(
                            'schools.*',
                            'school-registrations.*',
                            'activity-logs.*'
                        )"
                        class="grid"
                    >
                        <flux:sidebar.item
                            icon="building-office-2"
                            :href="route('schools.index')"
                            :current="request()->routeIs('schools.*')"
                            wire:navigate
                        >
                            Data Sekolah
                        </flux:sidebar.item>

                        @if ($currentUser?->isSuperAdmin())
                            <flux:sidebar.item
                                :href="route('school-registrations.index')"
                                :current="request()->routeIs('school-registrations.*')"
                                wire:navigate
                            >
                                Permohonan Sekolah
                            </flux:sidebar.item>

                            <flux:sidebar.item
                                :href="route('activity-logs.index')"
                                :current="request()->routeIs('activity-logs.*')"
                                wire:navigate
                            >
                                Log Aktivitas
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcan

            {{-- =================================================
            PENGATURAN
            Konfigurasi operasional dipusatkan di satu tempat.
            Item sekolah memakai data-school-menu secara individual sehingga
            pengaturan global tetap tersedia pada mode Global Super Admin.
            ================================================== --}}

            <flux:sidebar.nav>
                <flux:sidebar.group
                    heading="Pengaturan"
                    expandable
                    :expanded="request()->routeIs(
                        'notification-settings.*',
                        'settings.*',
                        'assessments.settings',
                        'letters.settings'
                    )"
                    class="grid"
                >
                    <flux:sidebar.item
                        icon="bell-alert"
                        :href="route('notification-settings.manage')"
                        :current="request()->routeIs('notification-settings.*')"
                        wire:navigate
                    >
                        Pengaturan Notifikasi
                    </flux:sidebar.item>

                    @can('school_documents.view')
                        <flux:sidebar.item
                            data-school-menu
                            @class([
                                'opacity-60 cursor-not-allowed' => ! $hasActiveSchool,
                            ])
                            icon="document-text"
                            :href="route('settings.school-documents')"
                            :current="request()->routeIs('settings.school-documents')"
                            wire:navigate
                        >
                            Dokumen Sekolah
                        </flux:sidebar.item>
                    @endcan

                    @can('attendance_score_settings.view')
                        <flux:sidebar.item
                            data-school-menu
                            @class([
                                'opacity-60 cursor-not-allowed' => ! $hasActiveSchool,
                            ])
                            icon="adjustments-horizontal"
                            :href="route('settings.attendance-scoring')"
                            :current="request()->routeIs('settings.attendance-scoring')"
                            wire:navigate
                        >
                            Bobot Kehadiran
                        </flux:sidebar.item>
                    @endcan

                    @can('assessments.view')
                        <flux:sidebar.item
                            data-school-menu
                            @class([
                                'opacity-60 cursor-not-allowed' => ! $hasActiveSchool,
                            ])
                            icon="clipboard-document-check"
                            :href="route('assessments.settings')"
                            :current="request()->routeIs('assessments.settings')"
                            wire:navigate
                        >
                            Pengaturan Penilaian
                        </flux:sidebar.item>
                    @endcan

                    @can('letters.settings')
                        <flux:sidebar.item
                            data-school-menu
                            @class([
                                'opacity-60 cursor-not-allowed' => ! $hasActiveSchool,
                            ])
                            icon="cog-6-tooth"
                            :href="route('letters.settings')"
                            :current="request()->routeIs('letters.settings')"
                            wire:navigate
                        >
                            Pengaturan Persuratan
                        </flux:sidebar.item>
                    @endcan

                    @if ($currentUser?->isSuperAdmin())
                        <flux:sidebar.item
                            icon="chat-bubble-left-ellipsis"
                            :href="route('settings.messaging')"
                            :current="request()->routeIs('settings.messaging')"
                            wire:navigate
                        >
                            Integrasi Pesan
                        </flux:sidebar.item>
                    @endif

                    @can('landing.manage')
                        <flux:sidebar.item
                            icon="wrench-screwdriver"
                            :href="route('settings.landing-content')"
                            :current="request()->routeIs('settings.landing-content')"
                            wire:navigate
                        >
                            Landing Page
                        </flux:sidebar.item>
                    @endcan

                    @if ($contentSchool)
                        @can('school-landing.manage', $contentSchool)
                            <flux:sidebar.item
                                data-school-menu
                                @class([
                                    'opacity-60 cursor-not-allowed' => ! $hasActiveSchool,
                                ])
                                icon="wrench-screwdriver"
                                :href="route('settings.tenant-content', $contentSchool)"
                                :current="request()->routeIs('settings.tenant-content')"
                                wire:navigate
                            >
                                Halaman Sekolah
                            </flux:sidebar.item>
                        @endcan
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>

        @endif
        
        <flux:spacer />

        {{-- =================================================
        USER MENU
        ================================================== --}}

        <x-desktop-user-menu
            class="hidden lg:block"
            :name="auth()->user()->name"
        />

    </flux:sidebar>

    {{-- =====================================================
    NAVBAR
    ====================================================== --}}

    <flux:header class="sticky top-0 z-20 border-b border-zinc-200 bg-[#f7f5ee] py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <flux:sidebar.toggle
            class="lg:hidden"
            icon="bars-2"
            inset="left"
        />

        <flux:spacer />

        <x-area-navigation />
    </flux:header>

    {{-- =====================================================
    ISI HALAMAN
    ====================================================== --}}

    {{ $slot }}

    {{-- =====================================================
    TOAST
    ====================================================== --}}

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    {{-- =====================================================
    FLUX / LIVEWIRE SCRIPTS
    ====================================================== --}}

    @fluxScripts

</body>

</html>
