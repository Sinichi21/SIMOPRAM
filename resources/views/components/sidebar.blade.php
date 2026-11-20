<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>

<body class="simpram-app min-h-screen bg-[#f7f5ee] text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    @php
        $currentUser = auth()->user();
        $currentSchool = app(\App\Support\SchoolContext::class)->school();
        $editorSchool = request()->routeIs('settings.tenant-content') ? request()->route('school') : null;
        $isStudent = $currentUser->hasRole('student');
        $availableModules = $isStudent ? [] : \App\Support\ModuleWorkspace::available($currentUser);
        $activeModule = \App\Support\ModuleWorkspace::activeSlug();
        $workspace = $availableModules[$activeModule]
            ?? ($activeModule ? \App\Support\ModuleWorkspace::find($activeModule) : null);
        $workspaceLinks = $availableModules[$activeModule]['links'] ?? [];
    @endphp

    <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
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

        @else
            <form method="POST" action="{{ route('school.switch') }}" class="px-2">
                @csrf
                <label for="sidebar-school" class="mb-1 block text-xs font-medium text-zinc-500 dark:text-zinc-400">
                    Sekolah Aktif
                </label>
                <select id="sidebar-school" name="school_id" onchange="if (this.value) this.form.submit()"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    @if ($currentUser->isSuperAdmin())
                        <option value="global" @selected(! $currentSchool)>Semua Sekolah / Global</option>
                    @elseif (! $currentSchool)
                        <option value="" selected disabled>Pilih sekolah aktif terlebih dahulu.</option>
                    @endif
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}" @selected($currentSchool?->id === $school->id)>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if ($editorSchool instanceof \App\Models\School && $editorSchool->id !== $currentSchool?->id)
                <p class="px-2 text-xs text-zinc-600 dark:text-zinc-400">Halaman sekolah {{ $editorSchool->name }}</p>
            @endif

            @if ($workspace)
                @include('modules.sidebar')
            @else
                <flux:sidebar.nav data-navigation="main">
                    <flux:sidebar.group heading="Beranda" class="grid">
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            Dashboard
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="squares-2x2" :href="route('modules.portal')" :current="request()->routeIs('modules.portal')" wire:navigate>
                            Portal Modul
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    @if ($availableModules !== [])
                        <flux:sidebar.group heading="Modul" class="grid">
                            @foreach ($availableModules as $module)
                                <flux:sidebar.item :icon="$module['icon']" :href="route('modules.show', $module['slug'])"
                                    class="h-auto! min-h-10 py-1! [&_[data-content]]:whitespace-normal" wire:navigate>
                                    {{ $module['title'] }}
                                </flux:sidebar.item>
                            @endforeach
                        </flux:sidebar.group>
                    @endif
                </flux:sidebar.nav>
            @endif

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Informasi Saya" class="grid">
                    @if ($currentSchool)
                        @can('attendances.self')
                            <flux:sidebar.item icon="map-pin" :href="route('attendances.self')" :current="request()->routeIs('attendances.self')" wire:navigate>
                                Absensi Saya
                            </flux:sidebar.item>
                        @endcan
                        @can('announcements.my')
                            <flux:sidebar.item icon="bell" :href="route('announcements.my')" :current="request()->routeIs('announcements.my')" wire:navigate>
                                Pengumuman Saya
                            </flux:sidebar.item>
                        @endcan
                    @endif
                    <flux:sidebar.item icon="calendar-days" :href="route('activity-access.mine')" :current="request()->routeIs('activity-access.mine')">
                        Kegiatan Umum Saya
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chat-bubble-left-right" :href="route('complaints.index')" :current="request()->routeIs('complaints.index', 'complaints.show')" wire:navigate>
                        Pengaduan
                    </flux:sidebar.item>
                    @if ($currentSchool)
                        <flux:sidebar.item icon="bell-alert" :href="route('notification-settings.manage')" :current="request()->routeIs('notification-settings.*')" wire:navigate>
                            Pengaturan Notifikasi
                        </flux:sidebar.item>
                    @endif
                    <flux:sidebar.item icon="user-circle" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                        Akun Saya
                    </flux:sidebar.item>
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
