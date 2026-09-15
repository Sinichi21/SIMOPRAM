@props([
    'school' => null,

    /*
    |--------------------------------------------------------------------------
    | Logo configuration
    |--------------------------------------------------------------------------
    |
    | logoVariant:
    | - logo = logo lengkap
    | - icon = icon saja
    |
    | logoTheme:
    | - auto  = logo.svg untuk light, logo-dark.svg untuk dark
    | - light = selalu logo.svg
    | - dark  = selalu logo-dark.svg
    |
    */

    'logoVariant' => 'logo',
    'logoTheme' => 'auto',
])

<header
    class="relative z-40 border-b border-zinc-200 bg-white text-zinc-900
           dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
>
    {{-- Header utama --}}
    <div
        class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4
               px-5 py-3 lg:px-8"
    >
        <a
            href="{{ $school ? route('schools.landing', $school) : route('home') }}"
            class="flex min-w-0 items-center gap-3"
        >
            @if ($school)

                {{-- ========================= --}}
                {{-- LANDING PAGE SEKOLAH --}}
                {{-- ========================= --}}

                @if ($school->logo)

                    <img
                        src="{{ Storage::disk('public')->url($school->logo) }}"
                        alt="Logo {{ $school->name }}"
                        class="size-14 shrink-0 rounded-xl bg-white object-contain p-1"
                    >

                @else

                    {{-- Jika sekolah belum mempunyai logo --}}
                    <x-app-logo-icon
                        variant="icon"
                        theme="auto"
                        class="size-14 shrink-0 object-contain"
                    />

                @endif

                <span class="min-w-0">
                    <strong
                        class="block max-w-xs truncate text-sm font-bold tracking-tight
                               sm:max-w-md"
                    >
                        {{ $school->name }}
                    </strong>

                    <span
                        class="text-[10px] font-bold tracking-widest
                               text-emerald-700 dark:text-emerald-300"
                    >
                        PRAMUKA DIGITAL
                    </span>
                </span>

            @else

                {{-- ========================= --}}
                {{-- LANDING PAGE GLOBAL --}}
                {{-- ========================= --}}

                @if ($logoVariant === 'icon')

                    <x-app-logo-icon
                        variant="icon"
                        :theme="$logoTheme"
                        class="size-14 shrink-0 object-contain sm:size-16"
                    />

                @else

                    <x-app-logo-icon
                        variant="logo"
                        :theme="$logoTheme"
                        class="h-14 w-auto shrink-0 sm:h-16"
                    />

                @endif

            @endif
        </a>

        {{-- User / area navigation --}}
        <x-area-navigation :school="$school" />
    </div>


    {{-- ========================================= --}}
    {{-- NAVIGASI HALAMAN --}}
    {{-- ========================================= --}}

    @if ($school || request()->routeIs('home', 'public.*'))

        <nav
            aria-label="Navigasi halaman"
            class="mx-auto flex max-w-7xl flex-wrap gap-x-6 gap-y-2
                   px-5 pb-3 text-sm font-semibold text-zinc-600
                   lg:px-8 dark:text-zinc-300"
        >

            @if ($school)

                <a
                    href="{{ route('schools.landing', $school) }}#profil"
                    class="transition hover:text-emerald-700
                           dark:hover:text-emerald-300"
                >
                    Profil sekolah
                </a>

                <a
                    href="{{ route('schools.landing', $school) }}#agenda"
                    class="transition hover:text-emerald-700
                           dark:hover:text-emerald-300"
                >
                    Pengumuman & kegiatan
                </a>

                <a
                    href="{{ route('schools.landing', $school) }}#dokumentasi"
                    class="transition hover:text-emerald-700
                           dark:hover:text-emerald-300"
                >
                    Dokumentasi
                </a>

                <a
                    href="{{ route('schools.landing', $school) }}#kontak"
                    class="transition hover:text-emerald-700
                           dark:hover:text-emerald-300"
                >
                    Kontak
                </a>

            @else

                <a href="{{ route('home') }}#agenda" class="transition hover:text-emerald-700 dark:hover:text-emerald-300">Pengumuman &amp; kegiatan</a>
                <a
                    href="{{ route('home') }}#fitur"
                    class="transition hover:text-emerald-700
                           dark:hover:text-emerald-300"
                >
                    Fitur
                </a>

                <a
                    href="{{ route('home') }}#sekolah"
                    class="transition hover:text-emerald-700
                           dark:hover:text-emerald-300"
                >
                    Sekolah
                </a>

                <a
                    href="{{ route('home') }}#daftar-sekolah"
                    class="transition hover:text-emerald-700
                           dark:hover:text-emerald-300"
                >
                    Daftarkan sekolah
                </a>

                <a
                    href="{{ route('complaints.public') }}"
                    class="transition hover:text-emerald-700
                           dark:hover:text-emerald-300"
                >
                    Pengaduan
                </a>

            @endif

        </nav>

    @endif
</header>
