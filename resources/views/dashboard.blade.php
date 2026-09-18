<x-layouts::app :title="__('Dashboard')">

    @if (
        auth()->user()
        ?->hasRole('student')
    )

        <livewire:dashboard.student-dashboard />

    @else

        <div class="p-6">
            <livewire:dashboard.index />
        </div>
    
    @endif
</x-layouts::app>