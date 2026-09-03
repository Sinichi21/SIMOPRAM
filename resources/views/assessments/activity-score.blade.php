<x-layouts::app
    :title="__('Input Nilai Kegiatan')"
>

    <div class="p-6">

        <livewire:assessments.activities.score
            :assessment-id="(int) request()->route('assessment')"
        />

    </div>

</x-layouts::app>
