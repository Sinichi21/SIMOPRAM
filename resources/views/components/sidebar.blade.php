<!DOCTYPE html>

<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="dark"
>

<head>
    @include('partials.head')
</head>

<body
    class="min-h-screen bg-white dark:bg-zinc-800"
>

    {{-- =====================================================
    SIDEBAR
    ====================================================== --}}

    <flux:sidebar
        sticky
        collapsible="mobile"
        class="border-e border-zinc-200
               bg-zinc-50
               dark:border-zinc-700
               dark:bg-zinc-900"
    >

        {{-- HEADER / LOGO --}}
        <flux:sidebar.header>

            <x-app-logo
                :sidebar="true"
                href="{{ route('dashboard') }}"
                wire:navigate
            />

            <flux:sidebar.collapse
                class="lg:hidden"
            />

        </flux:sidebar.header>


        {{-- =================================================
        DASHBOARD
        ================================================== --}}

        <flux:sidebar.nav>

            <flux:sidebar.group
                :heading="__('Menu Utama')"
                class="grid"
            >

                <flux:sidebar.item
                    icon="home"
                    :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')"
                    wire:navigate
                >
                    Dashboard
                </flux:sidebar.item>

            </flux:sidebar.group>

        </flux:sidebar.nav>


        {{-- =================================================
        ADMINISTRASI
        ================================================== --}}

        @can('schools.view')

            <flux:sidebar.nav>

                <flux:sidebar.group
                    heading="Administrasi"
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

                </flux:sidebar.group>

            </flux:sidebar.nav>

        @endcan


        {{-- =================================================
        SEKOLAH AKTIF
        ================================================== --}}

        @php
            $availableSchools =
                $availableSchools ?? collect();

            $activeSchool =
                $availableSchools->firstWhere(
                    'id',
                    (int) session('active_school_id')
                );
        @endphp


        <div class="px-3 py-3">

            <div
                class="mb-2 text-xs font-semibold
                       uppercase tracking-wide
                       text-zinc-500
                       dark:text-zinc-400"
            >
                Sekolah Aktif
            </div>


            <form
                method="POST"
                action="{{ route('school.switch') }}"
            >
                @csrf

                <select
                    name="school_id"
                    onchange="
                        if (this.value) {
                            this.form.submit();
                        }
                    "
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           text-sm text-zinc-900
                           outline-none
                           transition
                           focus:border-zinc-500
                           focus:ring-2
                           focus:ring-zinc-200
                           dark:border-zinc-700
                           dark:bg-zinc-800
                           dark:text-white
                           dark:focus:ring-zinc-700"
                >

                    <option value="">
                        -- Pilih Sekolah --
                    </option>

                    @foreach (
                        $availableSchools as $school
                    )

                        <option
                            value="{{ $school->id }}"
                            @selected(
                                (int)
                                session(
                                    'active_school_id'
                                )
                                ===
                                (int) $school->id
                            )
                        >
                            {{ $school->name }}
                        </option>

                    @endforeach

                </select>

            </form>


            @if ($activeSchool)

                <div
                    class="mt-3 rounded-lg
                           border border-zinc-200
                           bg-white px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                >

                    <div
                        class="text-xs
                               text-zinc-500
                               dark:text-zinc-400"
                    >
                        Sedang mengelola
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

            @endif

        </div>


        {{-- =================================================
        MASTER DATA
        ================================================== --}}

        <flux:sidebar.nav>

            <flux:sidebar.group
                heading="Master Data"
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


        {{-- =================================================
        KEGIATAN DAN ABSENSI
        ================================================== --}}

        @canany(['activities.view', 'attendance_sessions.view', 'journals.view', 'announcements.view', 'attendances.self'])

            <flux:sidebar.nav>

                <flux:sidebar.group
                    heading="Kegiatan"
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
                            :current="request()->routeIs('attendances.*')"
                            wire:navigate
                        >
                            Absensi
                        </flux:sidebar.item>

                    @endcan

                    @can('journals.view')

                        <flux:sidebar.item
                            icon="document-text"
                            :href="route('journals.index')"
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

                    @can('announcements.view')

                        <flux:sidebar.item
                            icon="megaphone"
                            :href="route('announcements.index')"
                            :current="
                                request()->routeIs(
                                    'announcements.index'
                                )
                                ||
                                request()->routeIs(
                                    'announcements.create'
                                )
                                ||
                                request()->routeIs(
                                    'announcements.edit'
                                )
                            "
                            wire:navigate
                        >
                            Pengumuman
                        </flux:sidebar.item>

                    @endcan

                </flux:sidebar.group>

                <flux:sidebar.group
                    heading="Informasi Saya"
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

                    <flux:sidebar.item
                        icon="cog-6-tooth"
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

            <flux:sidebar.nav>

                <flux:sidebar.group
                    heading="Penilaian"
                    class="grid"
                >

                    @can('assessments.view')

                        <flux:sidebar.item
                            icon="clipboard-document-check"
                            :href="route(
                                'assessments.settings'
                            )"
                            :current="
                                request()->routeIs(
                                    'assessments.settings'
                                )
                            "
                            wire:navigate
                        >
                            Pengaturan Penilaian
                        </flux:sidebar.item>

                    @endcan

                </flux:sidebar.group>

            </flux:sidebar.nav>

        @endcanany


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
    MOBILE HEADER
    ====================================================== --}}

    <flux:header class="lg:hidden">

        <flux:sidebar.toggle
            class="lg:hidden"
            icon="bars-2"
            inset="left"
        />

        <flux:spacer />

        <flux:dropdown
            position="top"
            align="end"
        >

            <flux:profile
                :initials="auth()->user()->initials()"
                icon-trailing="chevron-down"
            />

            <flux:menu>

                <flux:menu.radio.group>

                    <div
                        class="p-0 text-sm
                               font-normal"
                    >

                        <div
                            class="flex items-center
                                   gap-2 px-1 py-1.5
                                   text-start text-sm"
                        >

                            <flux:avatar
                                :name="auth()->user()->name"
                                :initials="auth()->user()->initials()"
                            />

                            <div
                                class="grid flex-1
                                       text-start text-sm
                                       leading-tight"
                            >

                                <flux:heading
                                    class="truncate"
                                >
                                    {{ auth()->user()->name }}
                                </flux:heading>

                                <flux:text
                                    class="truncate"
                                >
                                    {{ auth()->user()->email }}
                                </flux:text>

                            </div>

                        </div>

                    </div>

                </flux:menu.radio.group>


                <flux:menu.separator />


                <flux:menu.radio.group>

                    <flux:menu.item
                        :href="route('profile.edit')"
                        icon="cog"
                        wire:navigate
                    >
                        Settings
                    </flux:menu.item>

                </flux:menu.radio.group>


                <flux:menu.separator />


                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    class="w-full"
                >
                    @csrf

                    <flux:menu.item
                        as="button"
                        type="submit"
                        icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer"
                    >
                        Keluar
                    </flux:menu.item>

                </form>

            </flux:menu>

        </flux:dropdown>

    </flux:header>


    {{-- =====================================================
    INI SANGAT PENTING
    ISI HALAMAN DASHBOARD / SEKOLAH / SISWA / DLL
    DITAMPILKAN DI SINI
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
