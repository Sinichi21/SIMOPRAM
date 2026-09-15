<x-layouts::app :title="'Absensi kegiatan'">
    <livewire:activities.global-attendance :activity-id="(int) request()->route('activityId')" />
</x-layouts::app>
