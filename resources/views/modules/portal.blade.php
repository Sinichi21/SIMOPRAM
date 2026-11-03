<x-layouts::app :title="'Ruang Kerja SIMPRAM'">
    <div class="mx-auto max-w-6xl space-y-6 p-6">
        <div>
            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Portal SIMPRAM</p>
            <h1 class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">Pilih ruang kerja</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Setiap modul memiliki dashboard dan menu tersendiri, dengan akun dan sekolah aktif yang sama.</p>
        </div>

        @if (! app(\App\Support\SchoolContext::class)->hasSchool())
            <div role="status" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                Pilih sekolah aktif melalui pemilih sekolah di sidebar sebelum membuka ruang kerja modul.
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach (\App\Support\ModuleWorkspace::all() as $slug => $module)
                    @continue (! \App\Support\ModuleWorkspace::permitted(auth()->user(), $module))
                    <a href="{{ route('modules.show', $slug) }}" wire:navigate class="group rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:border-emerald-500 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:icon :name="$module['icon']" class="size-8 text-emerald-700 dark:text-emerald-400" />
                        <h2 class="mt-4 text-lg font-semibold group-hover:text-emerald-700 dark:group-hover:text-emerald-400">{{ $module['title'] }}</h2>
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $module['description'] }}</p>
                        <p class="mt-4 text-sm font-medium text-emerald-700 dark:text-emerald-400">Buka dashboard →</p>
                    </a>
                @endforeach
                <div class="rounded-2xl border border-dashed border-zinc-300 p-6 dark:border-zinc-700">
                    <flux:icon name="wallet" class="size-8 text-zinc-400" />
                    <h2 class="mt-4 text-lg font-semibold">Keuangan</h2>
                    <p class="mt-2 text-sm text-zinc-500">Disiapkan pada fase administrasi keuangan. Belum dapat diakses.</p>
                </div>
            </div>
        @endif
    </div>
</x-layouts::app>
