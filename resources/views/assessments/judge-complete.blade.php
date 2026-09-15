<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="referrer" content="no-referrer">
    @include('partials.head', ['title' => 'Penilaian Selesai'])
</head>
<body class="simpram-app min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <main class="app-content mx-auto flex min-h-screen max-w-xl flex-col justify-center gap-8 px-4 py-10 sm:px-6">
        <x-brand-logo class="mx-auto h-12 w-auto max-w-48" />
        <section class="rounded-xl border border-zinc-200 bg-white p-6 text-center sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto mb-5 flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
            </div>
            <h1 class="text-2xl font-bold">Penilaian sudah final</h1>
            <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">Terima kasih. Nilai telah dikunci dan link penilaian sudah ditutup.</p>
            <p class="mt-6 border-t border-zinc-200 pt-5 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">Halaman ini boleh ditutup.</p>
        </section>
    </main>
</body>
</html>
