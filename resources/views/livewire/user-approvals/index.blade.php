<div class="space-y-6">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div>
        <flux:heading size="xl">Manajemen User</flux:heading>
        <flux:text>Satu akun dapat digunakan di beberapa sekolah. Setujui pendaftaran, lalu kirim tautan agar pengguna mengatur password sendiri.</flux:text>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        <flux:select wire:model.live="status" label="Status akun">
            <flux:select.option value="pending">Menunggu persetujuan</flux:select.option>
            <flux:select.option value="approved">Akun sekolah</flux:select.option>
        </flux:select>
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
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Aksi</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($users as $user)
                <flux:table.row wire:key="approval-{{ $user->id }}">
                    <flux:table.cell>{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>{{ ['student' => 'Siswa', 'coach' => 'Pembina', 'school_admin' => 'Admin Sekolah'][$status === 'approved' ? $user->system_role : $user->requested_role] ?? $user->system_role }}</flux:table.cell>
                    <flux:table.cell>{{ $user->activation_pending ? 'Menunggu aktivasi' : ($user->is_active ? 'Aktif' : 'Belum aktif') }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($user->approval_status === 'pending')
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="primary" wire:click="approve({{ $user->id }})" wire:confirm="Setujui akun ini?">Setujui</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="reject({{ $user->id }})" wire:confirm="Tolak pendaftaran ini?">Tolak</flux:button>
                        </div>
                        @elseif (! $user->isSystemAdmin() && ($user->system_role !== 'school_admin' || auth()->user()->isSuperAdmin()) && ($user->is_active || $user->activation_pending))
                            @include('livewire.user-approvals.activation-link', ['linkAction' => $user->id.', ', 'activationLink' => $selectedUserId === $user->id ? $activationLink : null])
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="5">Tidak ada user untuk filter ini.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $users->links() }}
</div>
