<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => $school->name])
    <meta name="description" content="{{ $school->tagline ?: 'Profil dan kegiatan Pramuka '.$school->name }}">
    <style>:root { --school-color: {{ preg_match('/^#[0-9a-fA-F]{6}$/', $school->primary_color ?? '') ? $school->primary_color : '#166534' }}; }</style>
</head>
<body class="min-h-screen bg-[#f8f7f2] text-slate-900 antialiased">
    <header class="absolute inset-x-0 top-0 z-40 border-b border-white/15 text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 lg:px-8">
            <a href="{{ route('schools.landing', $school) }}" class="flex items-center gap-3">
                @if ($school->logo)<img src="{{ Storage::url($school->logo) }}" class="size-11 rounded-xl bg-white object-cover p-1" alt="Logo {{ $school->name }}">@else<span class="grid size-11 place-items-center rounded-xl bg-white font-black text-[var(--school-color)]">{{ str($school->name)->substr(0, 1) }}</span>@endif
                <span class="max-w-48 text-sm font-extrabold leading-tight sm:max-w-none">{{ $school->name }}</span>
            </a>
            <nav class="hidden gap-6 text-sm font-semibold md:flex"><a href="#profil">Profil</a><a href="#agenda">Agenda</a><a href="#dokumentasi">Dokumentasi</a><a href="#kontak">Kontak</a></nav>
            <a href="{{ route('login', ['school' => $school->slug]) }}" class="rounded-full bg-white px-5 py-2.5 text-sm font-bold text-[var(--school-color)]">Masuk</a>
        </div>
    </header>

    <main>
        <section class="relative grid min-h-[760px] place-items-end overflow-hidden bg-[var(--school-color)] text-white">
            @if ($school->hero_image)<img src="{{ Storage::url($school->hero_image) }}" class="absolute inset-0 size-full object-cover opacity-45" alt="Kegiatan {{ $school->name }}">@endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-black/20"></div><div class="absolute right-[-8rem] top-24 size-[34rem] rounded-full border-[90px] border-white/5"></div>
            <div class="relative mx-auto w-full max-w-7xl px-5 pb-20 pt-40 lg:px-8 lg:pb-24">
                <p class="text-sm font-bold uppercase tracking-[.22em] text-amber-300">Gerakan Pramuka · {{ $school->city ?: 'Indonesia' }}</p>
                <h1 class="mt-5 max-w-5xl text-5xl font-black leading-[.95] tracking-[-.045em] sm:text-7xl lg:text-8xl">{{ $school->tagline ?: 'Tumbuh tangguh, berkarya bersama.' }}</h1>
                <div class="mt-9 flex flex-wrap gap-3">@if ($school->registration_open)<a href="{{ route('register', ['school' => $school->id]) }}" class="rounded-full bg-amber-300 px-7 py-3.5 font-bold text-slate-950">Daftar sebagai anggota</a>@endif<a href="#agenda" class="rounded-full border border-white/50 px-7 py-3.5 font-bold">Lihat kegiatan</a></div>
            </div>
        </section>

        <section id="profil" class="px-5 py-20 lg:px-8"><div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[.8fr_1.2fr]">
            <div><p class="text-sm font-bold uppercase tracking-[.2em] text-[var(--school-color)]">Tentang gugus depan</p><h2 class="mt-3 text-4xl font-black tracking-tight">Pramuka di<br>{{ $school->name }}</h2></div>
            <div><p class="text-xl leading-9 text-slate-600">{{ $school->profile ?: 'Kegiatan Pramuka kami menjadi ruang belajar yang menyenangkan untuk membangun kemandirian, kepemimpinan, kepedulian, dan semangat gotong royong peserta didik.' }}</p>
                <dl class="mt-10 grid gap-4 sm:grid-cols-3"><div class="rounded-2xl bg-white p-5"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">NPSN</dt><dd class="mt-2 font-bold">{{ $school->npsn }}</dd></div><div class="rounded-2xl bg-white p-5"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Jenjang</dt><dd class="mt-2 font-bold">{{ $school->level ?: 'Sekolah' }}</dd></div><div class="rounded-2xl bg-white p-5"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Lokasi</dt><dd class="mt-2 font-bold">{{ $school->city ?: '-' }}</dd></div></dl>
            </div>
        </div></section>

        <section id="agenda" class="bg-slate-950 px-5 py-20 text-white lg:px-8"><div class="mx-auto max-w-7xl"><div class="flex items-end justify-between"><div><p class="text-sm font-bold uppercase tracking-[.2em] text-amber-300">Tetap terhubung</p><h2 class="mt-3 text-4xl font-black">Pengumuman & agenda</h2></div></div>
            <div class="mt-10 grid gap-5 lg:grid-cols-2"><div class="grid gap-4">@forelse($school->announcements as $announcement)<article class="rounded-3xl bg-white/10 p-6"><p class="text-xs font-bold uppercase tracking-widest text-amber-300">Pengumuman · {{ optional($announcement->published_at)->translatedFormat('d M Y') }}</p><h3 class="mt-3 text-xl font-bold">{{ $announcement->title }}</h3><p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-300">{{ strip_tags($announcement->body) }}</p></article>@empty<div class="rounded-3xl border border-dashed border-white/20 p-8 text-slate-400">Belum ada pengumuman publik.</div>@endforelse</div>
                <div class="grid gap-4">@forelse($school->activities->take(3) as $activity)<article class="flex gap-5 rounded-3xl bg-white p-6 text-slate-900"><div class="grid size-16 shrink-0 place-items-center rounded-2xl bg-amber-300 text-center"><span class="text-2xl font-black leading-none">{{ $activity->start_at->format('d') }}</span><span class="text-[10px] font-bold uppercase">{{ $activity->start_at->translatedFormat('M') }}</span></div><div><h3 class="font-bold">{{ $activity->title }}</h3><p class="mt-2 text-sm text-slate-500">{{ $activity->start_at->format('H.i') }} · {{ $activity->location ?: 'Lokasi menyusul' }}</p></div></article>@empty<div class="rounded-3xl border border-dashed border-white/20 p-8 text-slate-400">Belum ada agenda publik.</div>@endforelse</div>
            </div>
        </div></section>

        <section id="dokumentasi" class="px-5 py-20 lg:px-8"><div class="mx-auto max-w-7xl"><p class="text-sm font-bold uppercase tracking-[.2em] text-[var(--school-color)]">Cerita dari lapangan</p><h2 class="mt-3 text-4xl font-black">Dokumentasi kegiatan</h2>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@forelse($school->activities as $activity)<article class="group relative min-h-72 overflow-hidden rounded-3xl bg-[var(--school-color)] p-7 text-white"><div class="absolute -right-10 -top-10 size-40 rounded-full border-[25px] border-white/10 transition group-hover:scale-110"></div><div class="relative flex h-full flex-col justify-end"><p class="text-xs font-bold uppercase tracking-widest text-amber-300">{{ $activity->activity_type }} · {{ $activity->start_at->translatedFormat('M Y') }}</p><h3 class="mt-3 text-2xl font-black">{{ $activity->title }}</h3><p class="mt-3 line-clamp-2 text-sm leading-6 text-white/70">{{ $activity->description ?: $activity->location }}</p></div></article>@empty<div class="col-span-full rounded-3xl border border-dashed border-slate-300 p-12 text-center text-slate-500">Dokumentasi kegiatan akan segera hadir.</div>@endforelse</div>
        </div></section>

        <section id="kontak" class="px-5 pb-20 lg:px-8"><div class="mx-auto grid max-w-7xl overflow-hidden rounded-[2rem] bg-amber-300 lg:grid-cols-2"><div class="p-8 lg:p-12"><p class="text-sm font-bold uppercase tracking-[.2em] text-[var(--school-color)]">Hubungi kami</p><h2 class="mt-3 text-4xl font-black">Punya pertanyaan tentang Pramuka?</h2><p class="mt-5 max-w-md leading-7 text-slate-700">Pembina kami siap membantu informasi kegiatan dan pendaftaran anggota.</p><div class="mt-8 space-y-2 text-sm font-semibold">@if($school->phone)<p>Telepon · {{ $school->phone }}</p>@endif @if($school->email)<p>Email · {{ $school->email }}</p>@endif<p>Alamat · {{ $school->address ?: $school->city }}</p></div></div><div class="grid gap-px bg-slate-900/10 sm:grid-cols-2">@forelse($school->coaches as $coach)<div class="bg-white/60 p-8"><span class="grid size-12 place-items-center rounded-full bg-[var(--school-color)] font-bold text-white">{{ str($coach->name)->substr(0, 1) }}</span><h3 class="mt-5 font-bold">{{ $coach->name }}</h3><p class="mt-1 text-sm text-slate-600">{{ $coach->position ?: 'Pembina Pramuka' }}</p>@if($coach->phone)<a href="tel:{{ $coach->phone }}" class="mt-5 inline-block text-sm font-bold text-[var(--school-color)]">Hubungi pembina →</a>@endif</div>@empty<div class="col-span-full grid place-items-center p-10 text-sm text-slate-600">Kontak pembina akan segera diperbarui.</div>@endforelse</div></div></section>
    </main>
    <footer class="bg-[var(--school-color)] px-5 py-10 text-white lg:px-8"><div class="mx-auto flex max-w-7xl flex-col justify-between gap-5 sm:flex-row"><p class="font-bold">{{ $school->name }}</p><div class="flex gap-5 text-sm text-white/70"><a href="{{ route('home') }}">SIMPRAM</a><a href="{{ route('login', ['school' => $school->slug]) }}">Login anggota / pembina</a></div></div></footer>
    @fluxScripts
</body>
</html>
