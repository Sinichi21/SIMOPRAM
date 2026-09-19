<x-sidebar :title="$title ?? null">

    <flux:main class="app-content min-w-0 p-4! sm:p-6! lg:p-8!">
        {{ $slot }}
    </flux:main>

</x-sidebar>
