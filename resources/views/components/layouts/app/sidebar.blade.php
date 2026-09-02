<div class="space-y-1">

    <div class="px-3 py-2 text-xs font-semibold uppercase text-zinc-500">
        Master Data
    </div>

    @can('academic_years.view')
        <a
            href="{{ route('academic-years.index') }}"
            wire:navigate
            class="block rounded-lg px-3 py-2"
        >
            Tahun Ajaran
        </a>
    @endcan

    @can('semesters.view')
        <a
            href="{{ route('semesters.index') }}"
            wire:navigate
            class="block rounded-lg px-3 py-2"
        >
            Semester
        </a>
    @endcan

    @can('classrooms.view')
        <a
            href="{{ route('classrooms.index') }}"
            wire:navigate
            class="block rounded-lg px-3 py-2"
        >
            Kelas
        </a>
    @endcan

    @can('gudep.view')
        <a
            href="{{ route('scout-groups.index') }}"
            wire:navigate
            class="block rounded-lg px-3 py-2"
        >
            Gugus Depan
        </a>
    @endcan

</div>