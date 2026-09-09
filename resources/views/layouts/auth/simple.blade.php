<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="simpram-app min-h-screen bg-[#f7f5ee] text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <x-public-header />
        <main class="mx-auto grid max-w-6xl items-start gap-8 px-5 py-10 lg:grid-cols-2 lg:py-16">
            <section class="rounded-3xl bg-emerald-950 p-8 text-white lg:p-12">
                <p class="text-xs font-bold uppercase tracking-widest text-amber-300">Selamat datang di SIMPRAM</p>
                <h1 class="mt-5 text-4xl font-black tracking-tight lg:text-5xl">Pramuka tertata.<br>Karakter terbina.</h1>
                <p class="mt-6 leading-8 text-emerald-100">Satu ruang untuk mengelola kegiatan, mengikuti perkembangan, dan tumbuh bersama komunitas sekolah.</p>
                <a href="{{ route('home') }}#sekolah" class="mt-8 inline-block rounded-full bg-amber-300 px-6 py-3 font-bold text-emerald-950">Temukan sekolahmu</a>
            </section>
            <div class="flex w-full flex-col gap-6 rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-10 dark:border-zinc-800 dark:bg-zinc-900">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
