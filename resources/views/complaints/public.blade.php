<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Pengaduan'])
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="same-origin">
</head>
<body class="simpram-app min-h-screen bg-[#f7f5ee] text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <header class="border-b border-zinc-200 dark:border-zinc-800"><div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-5"><a href="{{ route('home') }}" class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-emerald-800 text-xl font-black text-amber-300">S</span><span class="font-black">SIMPRAM<span class="block text-[10px] tracking-widest text-emerald-700 dark:text-emerald-300">PRAMUKA DIGITAL</span></span></a><a href="{{ route('home') }}" class="text-sm font-bold">Kembali ke beranda</a></div></header>
    <main class="mx-auto max-w-6xl space-y-8 px-5 py-12">
        <section class="app-page-heading"><span class="inline-block rounded-full px-4 py-2 text-xs uppercase tracking-widest">Ruang untuk didengar</span><h1 class="mt-5 text-4xl sm:text-5xl">Sampaikan pengaduan Anda.</h1><p class="mt-4 max-w-2xl leading-7">Bantu kami menjaga kegiatan Pramuka yang aman dan layanan yang lebih baik. Pengaduan publik ditangani oleh super admin SIMPRAM.</p></section>
        @if (session('complaint-reference'))
            <div role="status" class="rounded-2xl border border-emerald-300 bg-emerald-50 p-6 text-emerald-950"><h2 class="font-bold">Pengaduan berhasil dikirim</h2><p class="mt-2">Simpan nomor berikut bersama email yang Anda gunakan untuk mengecek status dan tanggapan.</p><p class="mt-4 break-all font-mono text-lg font-bold">{{ session('complaint-reference') }}</p></div>
        @endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-5 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="grid items-start gap-8 lg:grid-cols-[1.35fr_1fr]">
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><h2 class="mb-6 text-xl font-bold">Buat pengaduan</h2>@include('complaints.form', ['action' => route('complaints.public.store'), 'internal' => false])</section>
            <div class="space-y-6">
                <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><h2 class="text-xl font-bold">Lacak pengaduan</h2><p class="mt-2 text-sm leading-6 text-zinc-500">Gunakan nomor pengaduan dan email saat mengirim. Pengaduan tidak ditampilkan dalam daftar publik.</p><form method="POST" action="{{ route('complaints.track') }}" class="mt-5 space-y-4">@csrf<div><label for="reference" class="mb-2 block text-sm font-semibold">Nomor pengaduan</label><input id="reference" name="reference" required maxlength="36" value="{{ old('reference', session('complaint-reference')) }}" placeholder="ADU-..." class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-zinc-900 focus:border-emerald-600 focus:outline-emerald-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"></div><div><label for="tracking_email" class="mb-2 block text-sm font-semibold">Email pelapor</label><input id="tracking_email" name="tracking_email" type="email" required value="{{ old('tracking_email') }}" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-zinc-900 focus:border-emerald-600 focus:outline-emerald-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"></div><button class="inline-flex items-center justify-center rounded-full bg-emerald-800 px-6 py-3 text-sm font-bold text-white hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-600">Cek status</button></form></section>
                @if ($trackedComplaint)<section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-label="Hasil pelacakan">@include('complaints.detail', ['complaint' => $trackedComplaint])</section>@endif
                <section class="rounded-2xl bg-amber-200 p-6 text-emerald-950"><h2 class="font-bold">Siswa atau pembina?</h2><p class="mt-2 text-sm leading-6">Masuk untuk menyampaikan pengaduan ke pengelola sekolah dan melihat riwayat pengaduan Anda.</p><a href="{{ route('complaints.index') }}" class="mt-4 inline-block font-bold underline underline-offset-4">Pengaduan sekolah →</a></section>
            </div>
        </div>
    </main>
    <footer class="border-t border-zinc-200 px-5 py-7 text-center text-sm text-zinc-500">© {{ date('Y') }} SIMPRAM. Bersama membina generasi.</footer>
    @fluxScripts
</body>
</html>
