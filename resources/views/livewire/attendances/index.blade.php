<div class="space-y-6">
    <div>
        <flux:heading size="xl">Absensi Kegiatan</flux:heading>
        <flux:text class="mt-1">
            Pilih kegiatan yang akan dikelola absensinya.
        </flux:text>
    </div>

    <flux:card class="space-y-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Cari kegiatan atau lokasi..."
        />

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Kegiatan</flux:table.column>
                <flux:table.column>Tanggal</flux:table.column>
                <flux:table.column>Lokasi</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($activities as $activity)
                    <flux:table.row wire:key="attendance-activity-{{ $activity->id }}">
                        <flux:table.cell variant="strong">
                            {{ $activity->title }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $activity->start_at->format('d-m-Y H:i') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $activity->location ?: '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge>{{ ucfirst($activity->status) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button
                                size="sm"
                                variant="primary"
                                :href="route('attendances.manage', $activity->id)"
                                wire:navigate
                            >
                                Kelola Absensi
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-8 text-center">
                            Belum ada kegiatan yang dapat dipilih.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $activities->links() }}
    </flux:card>
</div>
