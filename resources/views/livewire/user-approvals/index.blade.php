<div class="space-y-6">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div>
        <flux:heading size="xl">Persetujuan User</flux:heading>
        <flux:text>Setujui pendaftaran mandiri sebelum akun dapat login.</flux:text>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama atau email..." />
        <flux:select wire:model.live="role">
            <flux:select.option value="">Semua tipe akun</flux:select.option>
            <flux:select.option value="student">Siswa</flux:select.option>
            <flux:select.option value="coach">Pembina</flux:select.option>
            <flux:select.option value="school_admin">Admin Sekolah</flux:select.option>
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Nama</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Tipe</flux:table.column>
            <flux:table.column>Tanggal</flux:table.column>
            <flux:table.column>Aksi</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($users as $user)
                <flux:table.row wire:key="approval-{{ $user->id }}">
                    <flux:table.cell>{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>{{ ['student' => 'Siswa', 'coach' => 'Pembina', 'school_admin' => 'Admin Sekolah'][$user->requested_role] }}</flux:table.cell>
                    <flux:table.cell>{{ $user->created_at->format('d-m-Y H:i') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="primary" wire:click="approve({{ $user->id }})" wire:confirm="Setujui akun ini?">Setujui</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="reject({{ $user->id }})" wire:confirm="Tolak pendaftaran ini?">Tolak</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="5">Tidak ada pendaftaran yang menunggu persetujuan.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $users->links() }}
</div>
