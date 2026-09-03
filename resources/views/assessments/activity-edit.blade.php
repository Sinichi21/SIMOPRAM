<x-layouts::app
    :title="__('Kelola Penilaian Kegiatan')"
>

    <div class="p-6">

        <livewire:assessments.activities.edit
            :assessment-id="(int) request()->route('assessment')"
        />

    </div>

</x-layouts::app>