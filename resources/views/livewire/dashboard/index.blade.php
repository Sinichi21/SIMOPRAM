<div class="space-y-6">

    @if ($mode === 'global')

        @include(
            'livewire.dashboard.partials.global'
        )

    @elseif ($mode === 'school')

        @include(
            'livewire.dashboard.partials.school'
        )

    @else

        @include(
            'livewire.dashboard.partials.none'
        )

    @endif

</div>