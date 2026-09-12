@props([
    'variant' => 'full',
])

@if ($variant === 'icon')
    <img
        src="{{ asset('brand/logo-icon.svg') }}"
        alt="{{ config('app.name', 'SIMPRAM') }}"
        {{ $attributes->class('object-contain') }}
    >
@else
    {{-- Logo untuk light mode --}}
    <img
        src="{{ asset('brand/logo.svg') }}"
        alt="{{ config('app.name', 'SIMPRAM') }}"
        {{ $attributes->class('object-contain dark:hidden') }}
    >

    {{-- Logo untuk dark mode --}}
    <img
        src="{{ asset('brand/logo-dark.svg') }}"
        alt="{{ config('app.name', 'SIMPRAM') }}"
        {{ $attributes->class('hidden object-contain dark:block') }}
    >
@endif