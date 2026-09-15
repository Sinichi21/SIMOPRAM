<x-layouts::app title="Penilaian Kegiatan Umum">
    <livewire:assessments.activities.global-manage :activity-id="request()->integer('activity') ?: null" />
</x-layouts::app>
