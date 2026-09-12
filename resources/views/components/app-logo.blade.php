{{-- @props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif --}}
@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand
        {{-- :name="config('app.name', 'SIMPRAM')"
        {{ $attributes }} --}}
    >
        <x-slot name="logo" class="flex size-50 items-center justify-center">
            <x-app-logo-icon class="size-50 object-contain" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand
        :name="config('app.name', 'SIMPRAM')"
        {{ $attributes }}
    >
        <x-slot name="logo" class="flex size-50 items-center justify-center">
            <x-app-logo-icon class="size-50 object-contain" />
        </x-slot>
    </flux:brand>
@endif