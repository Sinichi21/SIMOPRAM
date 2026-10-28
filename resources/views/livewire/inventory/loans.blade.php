<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Peminjaman Inventaris</flux:heading>
            <flux:subheading>Permohonan dan persetujuan peminjaman barang berdasarkan sekolah aktif.</flux:subheading>
        </div>
        @can('inventory.loans.request')
            <flux:button variant="primary" icon="plus" wire:click="$set('showRequestForm', true)">Ajukan Peminjaman</flux:button>
        @endcan
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </flux:callout>
    @endif

    @if ($showRequestForm)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Permohonan Baru — Peminjam Eksternal</flux:heading>
            <flux:text size="sm">Tahap awal mendukung satu jenis barang stok kuantitas per permohonan. Barang belum dikeluarkan sampai serah-terima fisik.</flux:text>
            <form wire:submit="requestLoan" class="grid gap-4 md:grid-cols-2">
                <flux:input label="Nama peminjam" wire:model="borrowerName" required />
                <flux:input label="Organisasi (opsional)" wire:model="borrowerOrganization" />
                <flux:input label="Kontak (opsional)" wire:model="borrowerContact" />
                <flux:textarea label="Keperluan (opsional)" wire:model="purpose" />
                <flux:select label="Barang" wire:model="itemId" placeholder="Pilih barang" required>
                    @foreach ($items as $item)
                        <flux:select.option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select label="Pemilik" wire:model="ownerId" placeholder="Pilih pemilik" required>
                    @foreach ($owners as $owner)
                        <flux:select.option value="{{ $owner->id }}">{{ $owner->display_name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select label="Lokasi" wire:model="locationId" placeholder="Pilih lokasi" required>
                    @foreach ($locations as $location)
                        <flux:select.option value="{{ $location->id }}">{{ $location->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input label="Jumlah" type="number" step="0.001" min="0.001" wire:model="quantity" required />
                <div class="flex gap-2 md:col-span-2">
                    <flux:button type="submit" variant="primary">Simpan Permohonan</flux:button>
                    <flux:button type="button" variant="ghost" wire:click="$set('showRequestForm', false)">Batal</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">Daftar Permohonan</flux:heading>
            <div class="w-full sm:w-64">
                <flux:select wire:model.live="statusFilter" aria-label="Filter status">
                    <flux:select.option value="">Semua status</flux:select.option>
                    <flux:select.option value="requested">Menunggu persetujuan</flux:select.option>
                    <flux:select.option value="approved">Disetujui</flux:select.option>
                    <flux:select.option value="partially_handed_over">Serah-terima sebagian</flux:select.option>
                    <flux:select.option value="handed_over">Diserahkan</flux:select.option>
                    <flux:select.option value="partially_returned">Dikembalikan sebagian</flux:select.option>
                    <flux:select.option value="returned">Selesai</flux:select.option>
                    <flux:select.option value="discrepancy">Selisih / kerusakan</flux:select.option>
                </flux:select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b text-left"><th class="px-3 py-3">Nomor / Tanggal</th><th class="px-3 py-3">Peminjam</th><th class="px-3 py-3">Barang</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Tindakan</th></tr></thead>
                <tbody>
                    @forelse ($loans as $loan)
                        <tr wire:key="loan-{{ $loan->id }}" class="border-b last:border-0">
                            <td class="px-3 py-3"><span class="font-mono">{{ $loan->loan_number }}</span><div class="text-xs text-zinc-500">{{ $loan->requested_at?->format('d/m/Y H:i') }}</div></td>
                            <td class="px-3 py-3">{{ $loan->borrower_name }}<div class="text-xs text-zinc-500">{{ $loan->borrower_organization }}</div></td>
                            <td class="px-3 py-3">
                                @foreach ($loan->items as $line)
                                    <div>{{ $line->item?->name ?? 'Barang tidak tersedia' }} — {{ number_format((float) $line->quantity_requested, 3) }}</div>
                                @endforeach
                            </td>
                            <td class="px-3 py-3">{{ ['requested' => 'Menunggu', 'approved' => 'Disetujui', 'partially_handed_over' => 'Diserahkan sebagian', 'handed_over' => 'Diserahkan', 'partially_returned' => 'Kembali sebagian', 'returned' => 'Selesai', 'discrepancy' => 'Selisih / rusak'][$loan->status] ?? $loan->status }}</td>
                            <td class="px-3 py-3">
                                @can('inventory.loans.approve')
                                    @if ($loan->status === 'requested')
                                        <flux:button size="sm" variant="outline" wire:click="approveLoan({{ $loan->id }})" wire:confirm="Setujui permohonan ini?">Setujui</flux:button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-8 text-center text-zinc-500">Belum ada permohonan peminjaman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $loans->links() }}
    </flux:card>
</div>
