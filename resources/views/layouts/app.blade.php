<x-sidebar :title="$title ?? null">

    <flux:main class="app-content">
        {{ $slot }}
    </flux:main>

</x-sidebar>
