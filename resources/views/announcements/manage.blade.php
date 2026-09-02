<x-layouts::app :title="__('Kelola Pengumuman')">

    <div class="p-6">

        <livewire:announcements.manage
            :announcement-id="$announcementId ?? null"
        />

    </div>

</x-layouts::app>