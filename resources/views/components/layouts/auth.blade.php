<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'SIMPRAM') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @fluxAppearance
</head>

<body class="simpram-app min-h-screen bg-[#f7f5ee] text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <x-public-header />
    <main class="px-4 py-10 lg:py-16">
        <div class="mx-auto mb-8 max-w-3xl rounded-3xl bg-emerald-950 p-8 text-white">
            <p class="text-xs font-bold uppercase tracking-widest text-amber-300">Selamat datang di SIMPRAM</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight">Satu ruang untuk bertumbuh bersama.</h1>
            <p class="mt-3 leading-7 text-emerald-100">Terhubung dengan sekolah, ikuti kegiatan, dan kembangkan potensi Pramukamu.</p>
        </div>
        <div class="mx-auto w-full max-w-3xl rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-10 dark:border-zinc-800 dark:bg-zinc-900">
            {{ $slot }}
        </div>
    </main>

    @fluxScripts
</body>
</html>
