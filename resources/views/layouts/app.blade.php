<x-sidebar :title="$title ?? null">

    <flux:main>
        <div class="mb-6 flex justify-end">
            <x-area-navigation />
        </div>
        {{ $slot }}
    </flux:main>

</x-sidebar>
