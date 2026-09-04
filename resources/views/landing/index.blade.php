<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => 'SIMPRAM — Pramuka Terkelola, Generasi Berkarakter'])
    <meta name="description" content="Platform pengelolaan ekstrakurikuler Pramuka untuk sekolah Indonesia.">
</head>
<body class="min-h-screen bg-[#f7f5ee] text-slate-900 antialiased">
    <header class="sticky top-0 z-50 border-b border-emerald-950/10 bg-[#f7f5ee]/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="SIMPRAM Beranda">
                <span class="grid size-10 place-items-center rounded-xl bg-emerald-800 text-lg font-black text-amber-300">S</span>
                <span><strong class="block tracking-tight">SIMPRAM</strong><span class="block text-[10px] font-semibold tracking-[.18em] text-emerald-800">PRAMUKA DIGITAL</span></span>
            </a>
            <nav class="hidden items-center gap-7 text-sm font-semibold md:flex">
                <a href="#fitur" class="hover:text-emerald-700">Fitur</a><a href="#sekolah" class="hover:text-emerald-700">Sekolah</a><a href="#kontak" class="hover:text-emerald-700">Kontak</a>
            </nav>
            <a href="{{ route('login') }}" class="rounded-full border border-emerald-800 px-5 py-2.5 text-sm font-bold text-emerald-900 transition hover:bg-emerald-800 hover:text-white">Masuk Pengelola</a>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden px-5 py-20 lg:px-8 lg:py-28">
            <div class="absolute -right-24 top-10 size-96 rounded-full bg-amber-300/30 blur-3xl"></div>
            <div class="absolute -left-24 bottom-0 size-80 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="relative mx-auto grid max-w-7xl items-center gap-14 lg:grid-cols-[1.1fr_.9fr]">
                <div>
                    <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-emerald-800/15 bg-white/70 px-4 py-2 text-xs font-bold uppercase tracking-[.14em] text-emerald-800"><span class="size-2 rounded-full bg-amber-500"></span> Satu platform, seluruh kegiatan Pramuka</div>
                    <h1 class="max-w-3xl text-5xl font-black leading-[.95] tracking-[-.045em] text-emerald-950 sm:text-6xl lg:text-8xl">Pramuka tertata.<br><span class="text-emerald-700">Karakter terbina.</span></h1>
                    <p class="mt-7 max-w-xl text-lg leading-8 text-slate-600">SIMPRAM membantu sekolah mengelola anggota, presensi, kegiatan, penilaian, pengumuman, dan laporan dalam satu ruang kerja yang rapi.</p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="#sekolah" class="rounded-full bg-emerald-800 px-7 py-3.5 font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:-translate-y-0.5 hover:bg-emerald-700">Temukan sekolah</a>
                        <a href="#daftar-sekolah" class="rounded-full bg-amber-300 px-7 py-3.5 font-bold text-emerald-950 transition hover:-translate-y-0.5 hover:bg-amber-200">Daftarkan sekolah</a>
                    </div>
                </div>
                <div class="relative mx-auto w-full max-w-lg">
                    <div class="rotate-2 rounded-[2rem] bg-emerald-950 p-5 shadow-2xl shadow-emerald-950/25">
                        <div class="rounded-[1.4rem] bg-emerald-800 p-7 text-white">
                            <div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-200">Dashboard sekolah</p><p class="mt-2 text-2xl font-black">Gudep aktif hari ini</p></div><span class="grid size-12 place-items-center rounded-2xl bg-amber-300 text-2xl text-emerald-950">⚜</span></div>
                            <div class="mt-12 grid grid-cols-2 gap-3"><div class="rounded-2xl bg-white/10 p-5"><p class="text-4xl font-black">248</p><p class="mt-1 text-sm text-emerald-100">Anggota aktif</p></div><div class="rounded-2xl bg-amber-300 p-5 text-emerald-950"><p class="text-4xl font-black">94%</p><p class="mt-1 text-sm">Kehadiran</p></div></div>
                            <div class="mt-3 rounded-2xl bg-white p-5 text-slate-900"><p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Agenda berikutnya</p><p class="mt-2 font-bold">Latihan rutin & keterampilan tali-temali</p><div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full w-3/4 rounded-full bg-amber-400"></div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="fitur" class="bg-emerald-950 px-5 py-20 text-white lg:px-8">
            <div class="mx-auto max-w-7xl"><p class="text-sm font-bold uppercase tracking-[.2em] text-amber-300">Dari latihan hingga laporan</p><h2 class="mt-3 max-w-2xl text-4xl font-black tracking-tight sm:text-5xl">Semua yang dibutuhkan gugus depan.</h2>
                <div class="mt-12 grid gap-px overflow-hidden rounded-3xl bg-white/15 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([['01','Data anggota','Kelola siswa, pembina, regu, dan tingkatan secara terpusat.'],['02','Presensi cerdas','Catat kehadiran kegiatan dengan cepat dan akurat.'],['03','Agenda kegiatan','Susun latihan, acara, lokasi, serta target peserta.'],['04','Penilaian','Pantau capaian, nilai, dan perkembangan setiap anggota.'],['05','Jurnal & LPJ','Dokumentasi kegiatan dan laporan siap pakai.'],['06','Pengumuman','Sampaikan informasi tepat kepada komunitas sekolah.']] as $feature)
                        <article class="bg-emerald-950 p-8 transition hover:bg-emerald-900"><span class="font-mono text-sm text-amber-300">{{ $feature[0] }}</span><h3 class="mt-8 text-xl font-bold">{{ $feature[1] }}</h3><p class="mt-3 leading-7 text-emerald-100/70">{{ $feature[2] }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="sekolah" class="px-5 py-20 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end"><div><p class="text-sm font-bold uppercase tracking-[.2em] text-emerald-700">Direktori SIMPRAM</p><h2 class="mt-2 text-4xl font-black tracking-tight">Temukan sekolahmu</h2></div>
                    <form method="GET" action="{{ route('home') }}#sekolah" class="flex w-full max-w-lg rounded-full border border-slate-300 bg-white p-1.5 shadow-sm"><input name="q" value="{{ $search }}" class="min-w-0 flex-1 rounded-full border-0 bg-transparent px-5 focus:outline-none" placeholder="Nama sekolah, NPSN, atau kota"><button class="rounded-full bg-emerald-800 px-6 py-3 text-sm font-bold text-white">Cari</button></form>
                </div>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @forelse ($schools as $school)
                        <a href="{{ route('schools.landing', $school) }}" class="group rounded-3xl border border-slate-200 bg-white p-6 transition hover:-translate-y-1 hover:border-emerald-700 hover:shadow-xl">
                            <div class="flex items-center gap-4">@if ($school->logo)<img src="{{ Storage::url($school->logo) }}" alt="Logo {{ $school->name }}" class="size-14 rounded-2xl object-cover">@else<span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-xl font-black text-emerald-800">{{ str($school->name)->substr(0, 1) }}</span>@endif<div><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $school->level ?: 'Sekolah' }}</p><h3 class="mt-1 font-bold leading-tight">{{ $school->name }}</h3></div></div>
                            <p class="mt-6 text-sm text-slate-500">{{ $school->city ?: $school->province ?: 'Indonesia' }}</p><p class="mt-4 text-sm font-bold text-emerald-800">Lihat profil <span class="transition group-hover:translate-x-1">→</span></p>
                        </a>
                    @empty
                        <div class="col-span-full rounded-3xl border border-dashed border-slate-300 p-12 text-center text-slate-500">Belum ada sekolah yang cocok dengan pencarian “{{ $search }}”.</div>
                    @endforelse
                </div>
                <div class="mt-8">{{ $schools->links() }}</div>
            </div>
        </section>

        <section id="daftar-sekolah" class="px-5 pb-20 lg:px-8"><div class="mx-auto grid max-w-7xl overflow-hidden rounded-[2rem] bg-amber-300 lg:grid-cols-[.85fr_1.15fr]">
            <div class="p-8 lg:p-12"><p class="text-sm font-bold uppercase tracking-[.2em] text-emerald-800">Bergabung bersama kami</p><h2 class="mt-3 text-4xl font-black tracking-tight text-emerald-950">Bawa SIMPRAM ke sekolah Anda.</h2><p class="mt-5 leading-7 text-emerald-950/70">Kirim data singkat. Tim kami akan memverifikasi dan membantu menyiapkan ruang kerja sekolah.</p><div class="mt-10 space-y-3 text-sm font-semibold text-emerald-950"><p>✓ Landing page sekolah</p><p>✓ Pengelolaan kegiatan lengkap</p><p>✓ Onboarding pengelola tenant</p></div></div>
            <form method="POST" action="{{ route('school-registrations.store') }}#daftar-sekolah" class="grid gap-4 bg-white p-8 sm:grid-cols-2 lg:p-12">@csrf
                @if (session('school-registration-success'))<div class="col-span-full rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('school-registration-success') }}</div>@endif
                @foreach ([['school_name','Nama sekolah'],['npsn','NPSN'],['city','Kabupaten / kota'],['contact_name','Nama pengelola'],['contact_phone','Nomor WhatsApp'],['contact_email','Email pengelola']] as $field)<label class="grid gap-2 text-sm font-bold">{{ $field[1] }}<input name="{{ $field[0] }}" value="{{ old($field[0]) }}" class="rounded-xl border border-slate-300 px-4 py-3 font-normal focus:border-emerald-700 focus:outline-none" required>@error($field[0])<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>@endforeach
                <label class="grid gap-2 text-sm font-bold">Jenjang<select name="level" class="rounded-xl border border-slate-300 px-4 py-3 font-normal" required><option value="">Pilih jenjang</option>@foreach(['SD','SMP','SMA','SMK','Lainnya'] as $level)<option @selected(old('level') === $level)>{{ $level }}</option>@endforeach</select>@error('level')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                <label class="grid gap-2 text-sm font-bold sm:col-span-2">Catatan (opsional)<textarea name="notes" rows="3" class="rounded-xl border border-slate-300 px-4 py-3 font-normal">{{ old('notes') }}</textarea></label><button class="rounded-full bg-emerald-800 px-7 py-3.5 font-bold text-white sm:col-span-2">Kirim permohonan sekolah</button>
            </form>
        </div></section>
    </main>
    <footer id="kontak" class="border-t border-slate-200 bg-white px-5 py-10 lg:px-8"><div class="mx-auto flex max-w-7xl flex-col justify-between gap-5 text-sm text-slate-500 sm:flex-row"><p>© {{ date('Y') }} SIMPRAM. Bersama membina generasi.</p><div class="flex gap-5"><a href="mailto:halo@simpram.id" class="hover:text-emerald-700">halo@simpram.id</a><a href="{{ route('login') }}" class="font-bold text-emerald-800">Login pengelola</a></div></div></footer>
    @fluxScripts
</body>
</html>
