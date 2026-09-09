@props(['school' => null])
<header class="relative z-40 border-b border-zinc-200 bg-white text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4 lg:px-8">
        <a href="{{ $school ? route('schools.landing', $school) : route('home') }}" class="flex min-w-0 items-center gap-3">
            @if ($school?->logo)
                <img src="{{ Storage::disk('public')->url($school->logo) }}" alt="Logo {{ $school->name }}" class="size-11 shrink-0 rounded-xl bg-white object-contain p-1">
            @else
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-emerald-800 font-black text-amber-300">S</span>
            @endif
            <span><strong class="block text-sm tracking-tight">{{ $school?->name ?? 'SIMPRAM' }}</strong><span class="text-[10px] font-bold tracking-widest text-emerald-700 dark:text-emerald-300">PRAMUKA DIGITAL</span></span>
        </a>
        <x-area-navigation :school="$school" />
    </div>
    @if ($school || request()->routeIs('home'))
        <nav aria-label="Navigasi halaman" class="mx-auto flex max-w-7xl flex-wrap gap-x-6 gap-y-2 px-5 pb-4 text-sm font-semibold text-zinc-600 lg:px-8 dark:text-zinc-300">
            @if ($school)
                <a href="{{ route('schools.landing', $school) }}#profil">Profil sekolah</a>
                <a href="{{ route('schools.landing', $school) }}#agenda">Pengumuman & kegiatan</a>
                <a href="{{ route('schools.landing', $school) }}#dokumentasi">Dokumentasi</a>
                <a href="{{ route('schools.landing', $school) }}#kontak">Kontak</a>
            @else
                <a href="#fitur">Fitur</a><a href="#sekolah">Sekolah</a><a href="#daftar-sekolah">Daftarkan sekolah</a><a href="{{ route('complaints.public') }}">Pengaduan</a>
            @endif
        </nav>
    @endif
</header>
