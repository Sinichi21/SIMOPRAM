<x-layouts::app :title="$workspace['title']">
    <div class="mx-auto max-w-6xl space-y-6 p-6">
        <nav class="text-sm text-zinc-500" aria-label="Breadcrumb">
            <a href="{{ route('modules.portal') }}" wire:navigate class="hover:text-emerald-700">Ruang kerja</a>
            <span aria-hidden="true"> / </span>
            <span class="text-zinc-800 dark:text-zinc-200">{{ $workspace['title'] }}</span>
        </nav>
        <div>
            <h1 class="text-3xl font-semibold">{{ $workspace['title'] }}</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $workspace['description'] }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach (\App\Support\ModuleWorkspace::accessibleLinks(auth()->user(), $workspace) as $link)
                <a href="{{ route($link['route']) }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 font-medium transition hover:border-emerald-500 hover:text-emerald-700 dark:border-zinc-700 dark:bg-zinc-900">
                    {{ $link['label'] }} <span aria-hidden="true">→</span>
                </a>
            @endforeach
        </div>
        <p class="text-sm text-zinc-500">Fitur yang sudah ada tetap tersedia melalui menu di atas. Form dan laporan administrasi baru akan hadir pada fase berikutnya.</p>
    </div>
</x-layouts::app>
