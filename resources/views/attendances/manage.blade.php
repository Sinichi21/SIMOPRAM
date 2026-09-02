<x-layouts::app :title="__('Kelola Absensi')">

    <div class="p-6">

        <livewire:attendances.manage
            :activity-id="$activityId"
        />

    </div>

</x-layouts::app>