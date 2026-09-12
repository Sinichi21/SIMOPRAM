<div class="space-y-6">
    <div>
        <flux:heading size="xl">Permohonan Sekolah</flux:heading>
        <flux:text>Periksa pendaftaran mandiri sekolah dari landing page. Persetujuan membuat sekolah aktif di Data Sekolah.</flux:text>
    </div>

    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="grid gap-3 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" type="search" label="Cari permohonan" placeholder="Nama sekolah, NPSN, atau kota" />
        <flux:select wire:model.live="status" label="Status permohonan">
            <flux:select.option value="pending">Menunggu</flux:select.option>
            <flux:select.option value="approved">Disetujui</flux:select.option>
            <flux:select.option value="rejected">Ditolak</flux:select.option>
            <flux:select.option value="">Semua status</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Sekolah / NPSN</flux:table.column>
                <flux:table.column>Kota</flux:table.column>
                <flux:table.column>Penanggung jawab</flux:table.column>
                <flux:table.column>Diajukan</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($registrations as $registration)
                    <flux:table.row wire:key="school-registration-{{ $registration->id }}">
                        <flux:table.cell>
                            <div>{{ $registration->school_name }}</div>
                            <flux:text>{{ $registration->npsn }} · {{ $registration->level }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>{{ $registration->city }}</flux:table.cell>
                        <flux:table.cell>{{ $registration->contact_name }}</flux:table.cell>
                        <flux:table.cell>{{ $registration->created_at?->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$registration->status] ?? $registration->status }}</flux:table.cell>
                        <flux:table.cell><flux:button size="sm" wire:click="show({{ $registration->id }})">Detail</flux:button></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="6">Tidak ada permohonan untuk filter ini.</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
    {{ $registrations->links() }}

    <flux:modal wire:model="showDetail" class="w-full md:max-w-2xl">
        @if ($selected)
            <div class="space-y-5">
                <flux:heading size="lg">Detail Permohonan Sekolah</flux:heading>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt>Nama sekolah</dt><dd class="font-semibold">{{ $selected->school_name }}</dd></div>
                    <div><dt>NPSN</dt><dd>{{ $selected->npsn }}</dd></div>
                    <div><dt>Jenjang</dt><dd>{{ $selected->level }}</dd></div>
                    <div><dt>Kota</dt><dd>{{ $selected->city }}</dd></div>
                    <div><dt>Penanggung jawab</dt><dd>{{ $selected->contact_name }}</dd></div>
                    <div><dt>Telepon / WhatsApp</dt><dd>{{ $selected->contact_phone }}</dd></div>
                    <div class="sm:col-span-2"><dt>Email kontak</dt><dd class="break-words">{{ $selected->contact_email }}</dd></div>
                    <div class="sm:col-span-2"><dt>Catatan pendaftar</dt><dd class="whitespace-pre-wrap break-words">{{ $selected->notes ?: '-' }}</dd></div>
                    <div><dt>Status</dt><dd>{{ ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$selected->status] ?? $selected->status }}</dd></div>
                    <div><dt>Diajukan</dt><dd>{{ $selected->created_at?->format('d/m/Y H:i') }}</dd></div>
                    @if ($selected->reviewed_at)
                        <div><dt>Diproses oleh</dt><dd>{{ $selected->reviewer?->name ?? 'Pengguna sudah dihapus' }}</dd></div>
                        <div><dt>Waktu diproses</dt><dd>{{ $selected->reviewed_at->format('d/m/Y H:i') }}</dd></div>
                    @endif
                    @if ($selected->rejection_reason)
                        <div class="sm:col-span-2"><dt>Alasan penolakan</dt><dd class="whitespace-pre-wrap break-words">{{ $selected->rejection_reason }}</dd></div>
                    @endif
                </dl>
                <flux:error name="review" />
                @if ($selected->status === 'pending')
                    <flux:text>Setelah disetujui, lengkapi profil sekolah dan penyiapan akun admin sekolah. Hubungi penanggung jawab menggunakan kontak di atas untuk tindak lanjut.</flux:text>
                    <flux:textarea wire:model="rejectionReason" label="Alasan penolakan" placeholder="Wajib diisi jika permohonan ditolak" rows="3" />
                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="primary" wire:click="approve" wire:confirm="Setujui permohonan dan buat sekolah aktif?" wire:loading.attr="disabled">Setujui</flux:button>
                        <flux:button variant="danger" wire:click="reject" wire:confirm="Tolak permohonan sekolah ini?" wire:loading.attr="disabled">Tolak</flux:button>
                        <span wire:loading wire:target="approve,reject" role="status">Memproses...</span>
                    </div>
                @elseif ($selected->status === 'approved')
                    <flux:button :href="route('schools.index')" wire:navigate>Buka Data Sekolah</flux:button>
                @endif
            </div>
        @endif
    </flux:modal>
</div>
