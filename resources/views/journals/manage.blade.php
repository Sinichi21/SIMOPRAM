<x-layouts::app :title="__('Jurnal Kegiatan')">

    <div class="p-6">

        <livewire:journals.manage
            :activity-id="$activityId"
        />

    </div>

</x-layouts::app>