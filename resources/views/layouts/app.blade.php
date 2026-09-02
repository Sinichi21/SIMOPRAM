<x-sidebar :title="$title ?? null">

    <flux:main>
        {{ $slot }}
    </flux:main>

</x-sidebar>
