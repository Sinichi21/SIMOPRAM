<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => 'SIMPRAM — Pramuka Terkelola, Generasi Berkarakter'])
    <meta name="description" content="Platform pengelolaan ekstrakurikuler Pramuka untuk sekolah Indonesia.">
</head>
<body class="simpram-app min-h-screen bg-[#f7f5ee] text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <x-public-header />

    <main>
        <section class="relative overflow-hidden px-5 py-20 lg:px-8 lg:py-28">
            <div class="absolute -right-24 top-10 size-96 rounded-full bg-amber-300/30 blur-3xl"></div>
            <div class="absolute -left-24 bottom-0 size-80 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="relative mx-auto grid max-w-7xl items-center gap-14 lg:grid-cols-[1.1fr_.9fr]">
                <div>
                    <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-emerald-800/15 bg-white/70 px-4 py-2 text-xs font-bold uppercase tracking-[.14em] text-emerald-800"><span class="size-2 rounded-full bg-amber-500"></span> {{ $content['hero_badge'] }}</div>
                    <h1 class="max-w-3xl text-5xl font-black leading-[.95] tracking-[-.045em] text-emerald-950 dark:text-emerald-100 sm:text-6xl lg:text-8xl">{{ $content['hero_title'] }}<br><span class="text-emerald-700 dark:text-emerald-400">{{ $content['hero_highlight'] }}</span></h1>
                    <p class="mt-7 max-w-xl text-lg leading-8 text-slate-600 dark:text-zinc-300">{{ $content['hero_description'] }}</p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="#sekolah" class="rounded-full bg-emerald-800 px-7 py-3.5 font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:-translate-y-0.5 hover:bg-emerald-700">{{ $content['primary_button'] }}</a>
                        <a href="#daftar-sekolah" class="rounded-full bg-amber-300 px-7 py-3.5 font-bold text-emerald-950 transition hover:-translate-y-0.5 hover:bg-amber-200">{{ $content['secondary_button'] }}</a>
                    </div>
                </div>
                <div class="relative mx-auto w-full max-w-lg">
                    @if ($heroImage)
                        <img src="{{ Storage::disk('public')->url($heroImage) }}" alt="{{ $content['hero_title'] }}" class="aspect-square w-full rounded-3xl object-cover shadow-xl">
                    @else
                    <div class="rotate-2 rounded-[2rem] bg-emerald-950 p-5 shadow-2xl shadow-emerald-950/25">
                        <div class="rounded-[1.4rem] bg-emerald-800 p-7 text-white">
                            <div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-200">Dashboard sekolah</p><p class="mt-2 text-2xl font-black">Gudep aktif hari ini</p></div><span class="grid size-12 place-items-center rounded-2xl bg-amber-300 text-2xl text-emerald-950">⚜</span></div>
                            <div class="mt-12 grid grid-cols-2 gap-3"><div class="rounded-2xl bg-white/10 p-5"><p class="text-4xl font-black">248</p><p class="mt-1 text-sm text-emerald-100">Anggota aktif</p></div><div class="rounded-2xl bg-amber-300 p-5 text-emerald-950"><p class="text-4xl font-black">94%</p><p class="mt-1 text-sm">Kehadiran</p></div></div>
                            <div class="mt-3 rounded-2xl bg-white p-5 text-slate-900 dark:bg-zinc-900 dark:text-zinc-100 dark:border-zinc-700"><p class="text-xs font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Agenda berikutnya</p><p class="mt-2 font-bold">Latihan rutin & keterampilan tali-temali</p><div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full w-3/4 rounded-full bg-amber-400"></div></div></div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </section>

        <section id="fitur" class="bg-emerald-950 px-5 py-20 text-white lg:px-8">
            <div class="mx-auto max-w-7xl"><p class="text-sm font-bold uppercase tracking-[.2em] text-amber-300">{{ $content['features_label'] }}</p><h2 class="mt-3 max-w-2xl text-4xl font-black tracking-tight sm:text-5xl">{{ $content['features_title'] }}</h2>
                <div class="mt-12 grid gap-px overflow-hidden rounded-3xl bg-white/15 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (range(1, 6) as $number)
                        <article class="bg-emerald-950 p-8"><span class="font-mono text-sm text-amber-300">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span><h3 class="mt-8 text-xl font-bold">{{ $content['feature_'.$number.'_title'] }}</h3><p class="mt-3 leading-7 text-emerald-100/70">{{ $content['feature_'.$number.'_description'] }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="sekolah" class="px-5 py-20 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end"><div><p class="text-sm font-bold uppercase tracking-[.2em] text-emerald-700 dark:text-emerald-400">{{ $content['directory_label'] }}</p><h2 class="mt-2 text-4xl font-black tracking-tight">{{ $content['directory_title'] }}</h2></div>
                    <form method="GET" action="{{ route('home') }}#sekolah" class="flex w-full max-w-lg rounded-full border border-slate-300 bg-white p-1.5 shadow-sm dark:bg-zinc-900 dark:text-zinc-100 dark:border-zinc-700"><input name="q" value="{{ $search }}" class="min-w-0 flex-1 rounded-full border-0 bg-transparent px-5 focus:outline-none" placeholder="Nama sekolah, NPSN, atau kota"><button class="rounded-full bg-emerald-800 px-6 py-3 text-sm font-bold text-white">Cari</button></form>
                </div>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @forelse ($schools as $school)
                        <a href="{{ route('schools.landing', $school) }}" class="group rounded-3xl border border-slate-200 bg-white p-6 transition hover:-translate-y-1 hover:border-emerald-700 hover:shadow-xl dark:bg-zinc-900 dark:text-zinc-100 dark:border-zinc-700">
                            <div class="flex items-center gap-4">@if ($school->logo)<img src="{{ Storage::url($school->logo) }}" alt="Logo {{ $school->name }}" class="size-14 rounded-2xl object-cover">@else<span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-xl font-black text-emerald-800">{{ str($school->name)->substr(0, 1) }}</span>@endif<div><p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">{{ $school->level ?: 'Sekolah' }}</p><h3 class="mt-1 font-bold leading-tight">{{ $school->name }}</h3></div></div>
                            <p class="mt-6 text-sm text-slate-500 dark:text-zinc-400">{{ $school->city ?: $school->province ?: 'Indonesia' }}</p><p class="mt-4 text-sm font-bold text-emerald-800">Lihat profil <span class="transition group-hover:translate-x-1">→</span></p>
                        </a>
                    @empty
                        <div class="col-span-full rounded-3xl border border-dashed border-slate-300 p-12 text-center text-slate-500 dark:text-zinc-400">Belum ada sekolah yang cocok dengan pencarian “{{ $search }}”.</div>
                    @endforelse
                </div>
                <div class="mt-8">{{ $schools->links() }}</div>
            </div>
        </section>

        <section id="daftar-sekolah" class="px-5 pb-20 lg:px-8"><div class="mx-auto grid max-w-7xl overflow-hidden rounded-[2rem] bg-amber-300 text-slate-900 lg:grid-cols-[.85fr_1.15fr]">
            <div class="p-8 lg:p-12"><p class="text-sm font-bold uppercase tracking-[.2em] text-emerald-800">{{ $content['registration_label'] }}</p><h2 class="mt-3 text-4xl font-black tracking-tight text-emerald-950">{{ $content['registration_title'] }}</h2><p class="mt-5 leading-7 text-emerald-950/70">{{ $content['registration_description'] }}</p><div class="mt-10 space-y-3 text-sm font-semibold text-emerald-950"><p>✓ Landing page sekolah</p><p>✓ Pengelolaan kegiatan lengkap</p><p>✓ Onboarding pengelola tenant</p></div></div>
            <form method="POST" action="{{ route('school-registrations.store') }}#daftar-sekolah" class="grid gap-4 bg-white p-8 sm:grid-cols-2 lg:p-12 dark:bg-zinc-900 dark:text-zinc-100 dark:border-zinc-700">@csrf
                @if (session('school-registration-success'))<div class="col-span-full rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('school-registration-success') }}</div>@endif
                @foreach ([['school_name','Nama sekolah'],['npsn','NPSN'],['city','Kabupaten / kota'],['contact_name','Nama pengelola'],['contact_phone','Nomor WhatsApp'],['contact_email','Email pengelola']] as $field)<label class="grid gap-2 text-sm font-bold">{{ $field[1] }}<input name="{{ $field[0] }}" value="{{ old($field[0]) }}" class="rounded-xl border border-slate-300 px-4 py-3 font-normal focus:border-emerald-700 focus:outline-none" required>@error($field[0])<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>@endforeach
                <label class="grid gap-2 text-sm font-bold">Jenjang<select name="level" class="rounded-xl border border-slate-300 px-4 py-3 font-normal" required><option value="">Pilih jenjang</option>@foreach(['SD','SMP','SMA','SMK','Lainnya'] as $level)<option @selected(old('level') === $level)>{{ $level }}</option>@endforeach</select>@error('level')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                <label class="grid gap-2 text-sm font-bold sm:col-span-2">Catatan (opsional)<textarea name="notes" rows="3" class="rounded-xl border border-slate-300 px-4 py-3 font-normal">{{ old('notes') }}</textarea></label><button class="rounded-full bg-emerald-800 px-7 py-3.5 font-bold text-white sm:col-span-2">Kirim permohonan sekolah</button>
            </form>
        </div></section>
    </main>
    <footer id="kontak" class="border-t border-slate-200 bg-white px-5 py-10 lg:px-8 dark:bg-zinc-900 dark:text-zinc-100 dark:border-zinc-700"><div class="mx-auto flex max-w-7xl flex-col justify-between gap-5 text-sm text-slate-500 sm:flex-row dark:text-zinc-400"><p>© {{ date('Y') }} {{ $content['footer_text'] }}</p><div class="flex gap-5"><a href="{{ route('complaints.public') }}" class="hover:text-emerald-700">Pengaduan</a><a href="mailto:{{ $content['contact_email'] }}" class="hover:text-emerald-700">{{ $content['contact_email'] }}</a><a href="{{ route('login') }}" class="font-bold text-emerald-800">Login pengelola</a></div></div></footer>
    @fluxScripts
</body>
</html>
