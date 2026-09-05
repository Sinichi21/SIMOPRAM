<div class="space-y-6">
    <flux:heading size="xl">Akun {{ $type === 'coach' ? 'Pembina' : 'Siswa' }}</flux:heading>
    <flux:text>Hubungkan data dengan email pengguna. Akun yang sama dapat memiliki keanggotaan di beberapa sekolah.</flux:text>
    <flux:input wire:model.live.debounce.300ms="search" type="search" label="Cari nama atau email" />
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Nama</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Status akun</flux:table.column>
            <flux:table.column>Aksi</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($profiles as $profile)
                <flux:table.row wire:key="account-{{ $type }}-{{ $profile->id }}">
                    <flux:table.cell>{{ $profile->name }}</flux:table.cell>
                    <flux:table.cell>{{ $profile->user?->email ?? 'Belum terhubung' }}</flux:table.cell>
                    <flux:table.cell>{{ ! $profile->user ? 'Belum memiliki akun' : ($profile->user->activation_pending ? 'Menunggu aktivasi' : ($profile->user->is_active ? 'Aktif' : 'Tidak aktif')) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" :href="route($type.'-accounts.manage', [$type.'Id' => $profile->id])" wire:navigate>Kelola Akun</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="4">Tidak ada data pengguna di sekolah ini.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    {{ $profiles->links() }}
</div>
