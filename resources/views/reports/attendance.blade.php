<x-layouts::app :title="__('Rekap Absensi')">

    <div class="p-6">
        <flux:button :href="route('reports.attendance.detail')" wire:navigate>Rekap Detail per Pertemuan</flux:button>
        <livewire:reports.attendance />
    </div>

</x-layouts::app>
