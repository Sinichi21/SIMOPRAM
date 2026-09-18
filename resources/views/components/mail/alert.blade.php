@props([
    'type' => 'info',
])

@php
    $background = match ($type) {
        'warning' => '#fef3c7',
        'danger' => '#fee2e2',
        'success' => '#dcfce7',
        default => '#e0f2fe',
    };

    $border = match ($type) {
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
        'success' => '#22c55e',
        default => '#0ea5e9',
    };
@endphp

<div
    style="
        padding: 14px 16px;
        margin: 20px 0;
        background-color: {{ $background }};
        border-left: 4px solid {{ $border }};
        border-radius: 6px;
    "
>
    {{ $slot }}
</div>