@can('schools.view')

    <div class="mb-4 space-y-1">

        <div
            class="px-3 py-2 text-xs
                   font-semibold uppercase
                   tracking-wide text-zinc-500"
        >
            Administrasi
        </div>

        <a
            href="{{ route('schools.index') }}"
            wire:navigate
            @class([
                'block rounded-lg px-3 py-2
                 text-sm font-medium transition',

                'bg-zinc-900 text-white
                 dark:bg-white dark:text-zinc-900'
                    => request()->routeIs(
                        'schools.*'
                    ),

                'text-zinc-700
                 hover:bg-zinc-100
                 dark:text-zinc-300
                 dark:hover:bg-zinc-800'
                    => ! request()->routeIs(
                        'schools.*'
                    ),
            ])
        >
            Data Sekolah
        </a>

    </div>

@endcan

{{-- =========================
PEMILIH SEKOLAH AKTIF
========================= --}}

<form
    method="POST"
    action="{{ route('school.switch') }}"
    class="mb-3"
>
    @csrf

<div class="space-y-2">

    <label
        for="school_id"
        class="block text-xs font-semibold uppercase tracking-wide text-zinc-500"
    >
        Sekolah Aktif
    </label>

    <select
        id="school_id"
        name="school_id"
        onchange="if (this.value) this.form.submit()"
        class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm
               text-zinc-900 outline-none transition
               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
               dark:focus:border-zinc-500 dark:focus:ring-zinc-800"
    >
        <option value="">
            -- Pilih Sekolah --
        </option>

        @foreach ($availableSchools ?? collect() as $school)
            <option
                value="{{ $school->id }}"
                @selected(
                    (int) session('active_school_id') === (int) $school->id
                )
            >
                {{ $school->name }}
            </option>
        @endforeach
    </select>

</div>

</form>

{{-- =========================
INFORMASI SEKOLAH AKTIF
========================= --}}

@if (session('active_school_id'))

@php
    $activeSchool = $availableSchools->firstWhere(
        'id',
        (int) session('active_school_id')
    );
@endphp

@if ($activeSchool)

    <div
        class="mb-4 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2
               dark:border-zinc-700 dark:bg-zinc-800/70"
    >

        <div class="text-xs text-zinc-500 dark:text-zinc-400">
            Sedang mengelola
        </div>

        <div
            class="mt-0.5 truncate text-sm font-semibold text-zinc-900
                   dark:text-zinc-100"
            title="{{ $activeSchool->name }}"
        >
            {{ $activeSchool->name }}
        </div>

    </div>

@endif

@endif

{{-- =========================
MENU MASTER DATA
========================= --}}

<div class="space-y-1">

<div
    class="px-3 py-2 text-xs font-semibold uppercase tracking-wide
           text-zinc-500 dark:text-zinc-400"
>
    Master Data
</div>


{{-- Tahun Ajaran --}}
@can('academic_years.view')

    <a
        href="{{ route('academic-years.index') }}"
        wire:navigate
        @class([
            'block rounded-lg px-3 py-2 text-sm font-medium transition',

            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                => request()->routeIs('academic-years.*'),

            'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900
             dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white'
                => ! request()->routeIs('academic-years.*'),
        ])
    >
        Tahun Ajaran
    </a>

@endcan


{{-- Semester --}}
@can('semesters.view')

    <a
        href="{{ route('semesters.index') }}"
        wire:navigate
        @class([
            'block rounded-lg px-3 py-2 text-sm font-medium transition',

            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                => request()->routeIs('semesters.*'),

            'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900
             dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white'
                => ! request()->routeIs('semesters.*'),
        ])
    >
        Semester
    </a>

@endcan


{{-- Kelas --}}
@can('classrooms.view')

    <a
        href="{{ route('classrooms.index') }}"
        wire:navigate
        @class([
            'block rounded-lg px-3 py-2 text-sm font-medium transition',

            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                => request()->routeIs('classrooms.*'),

            'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900
             dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white'
                => ! request()->routeIs('classrooms.*'),
        ])
    >
        Kelas
    </a>

@endcan


{{-- Gugus Depan --}}
@can('gudep.view')

    <a
        href="{{ route('scout-groups.index') }}"
        wire:navigate
        @class([
            'block rounded-lg px-3 py-2 text-sm font-medium transition',

            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                => request()->routeIs('scout-groups.*'),

            'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900
             dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white'
                => ! request()->routeIs('scout-groups.*'),
        ])
    >
        Gugus Depan
    </a>

@endcan

{{-- Pembina --}}
@can('coaches.view')

    <a
        href="{{ route('coaches.index') }}"
        wire:navigate
        @class([
            'block rounded-lg px-3 py-2
             text-sm font-medium transition',

            'bg-zinc-900 text-white
             dark:bg-white dark:text-zinc-900'
                => request()->routeIs(
                    'coaches.*'
                ),

            'text-zinc-700
             hover:bg-zinc-100
             hover:text-zinc-900
             dark:text-zinc-300
             dark:hover:bg-zinc-800
             dark:hover:text-white'
                => ! request()->routeIs(
                    'coaches.*'
                ),
        ])
    >
        Pembina
    </a>

@endcan

{{-- Siswa --}}
@can('students.view')

    <a
        href="{{ route('students.index') }}"
        wire:navigate
        @class([
            'block rounded-lg px-3 py-2
             text-sm font-medium transition',

            'bg-zinc-900 text-white
             dark:bg-white dark:text-zinc-900'
                => request()->routeIs(
                    'students.*'
                ),

            'text-zinc-700
             hover:bg-zinc-100
             hover:text-zinc-900
             dark:text-zinc-300
             dark:hover:bg-zinc-800
             dark:hover:text-white'
                => ! request()->routeIs(
                    'students.*'
                ),
        ])
    >
        Siswa
    </a>

@endcan

@can('user_approvals.manage')
    <a
        href="{{ route('user-approvals.index') }}"
        wire:navigate
        @class([
            'block rounded-lg px-3 py-2 text-sm font-medium transition',
            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => request()->routeIs('user-approvals.*'),
            'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! request()->routeIs('user-approvals.*'),
        ])
    >
        Persetujuan User
    </a>
@endcan

@can('attendances.self')

    <flux:sidebar.item
        icon="map-pin"
        :href="route('attendances.self')"
        :current="
            request()->routeIs(
                'attendances.self'
            )
        "
        wire:navigate
    >
        Absensi Saya
    </flux:sidebar.item>

@endcan

{{-- Satuan --}}
@can('scout_units.view')

    <a
        href="{{ route('scout-units.index') }}"
        wire:navigate
        @class([
            'block rounded-lg px-3 py-2
             text-sm font-medium transition',

            'bg-zinc-900 text-white
             dark:bg-white dark:text-zinc-900'
                => request()->routeIs(
                    'scout-units.*'
                ),

            'text-zinc-700
             hover:bg-zinc-100
             hover:text-zinc-900
             dark:text-zinc-300
             dark:hover:bg-zinc-800
             dark:hover:text-white'
                => ! request()->routeIs(
                    'scout-units.*'
                ),
        ])
    >
        Regu / Barung
    </a>

@endcan
</div>

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

        {{-- @can('attendance_sessions.view')

            <flux:sidebar.item
                icon="clipboard-document-check"
                :href="route('attendances.index')"
                :current="request()->routeIs('attendances.*')"
                wire:navigate
            >
                Absensi
            </flux:sidebar.item>

        @endcan --}}

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

</flux:sidebar.nav>

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
