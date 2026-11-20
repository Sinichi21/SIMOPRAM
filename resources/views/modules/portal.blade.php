<x-layouts::app title="Portal SIMPRAM">
    @php
        $availableModules = \App\Support\ModuleWorkspace::available(auth()->user());
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <h1 class="text-3xl font-semibold">Portal SIMPRAM</h1>

        @if (! app(\App\Support\SchoolContext::class)->hasSchool())
            <p role="status" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                Pilih sekolah aktif di sidebar untuk membuka modul sekolah.
            </p>
        @elseif ($availableModules === [])
            <p role="status" class="text-sm text-zinc-600 dark:text-zinc-400">
                Belum ada modul yang dapat diakses dengan akun ini.
            </p>
        @endif

        @if ($availableModules !== [])
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($availableModules as $module)
                    <a href="{{ route('modules.show', $module['slug']) }}" wire:navigate class="group rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:border-emerald-500 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:icon :name="$module['icon']" class="size-8 text-emerald-700 dark:text-emerald-400" />
                        <h2 class="mt-4 text-lg font-semibold group-hover:text-emerald-700 dark:group-hover:text-emerald-400">{{ $module['title'] }}</h2>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $module['description'] }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts::app>
