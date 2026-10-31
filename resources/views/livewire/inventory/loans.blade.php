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
            <flux:heading size="lg">Permohonan Baru — Keranjang Peminjaman</flux:heading>
            <flux:text size="sm">Tambahkan beberapa barang atau unit aset ke keranjang. Stok baru dikeluarkan saat serah-terima fisik.</flux:text>
            <div class="grid gap-3 md:grid-cols-2">
                <flux:input label="Nama peminjam" wire:model="borrowerName" required />
                <flux:input label="Organisasi (opsional)" wire:model="borrowerOrganization" />
                <flux:input label="Kontak peminjam" wire:model="borrowerContact" required />
                <flux:textarea label="Keperluan peminjaman" wire:model="purpose" required />
            </div>
            <div class="rounded-lg border p-4 space-y-3" x-data="inventoryLoanScanner($wire)">
                <form wire:submit="findByCode" class="space-y-3">
                    <flux:input label="Kode barang / kode unit / QR" wire:model="itemCode" autocomplete="off" placeholder="Ketik kode atau pindai dengan scanner, lalu Enter" />
                    <div class="flex flex-wrap gap-2">
                        <flux:button type="submit" wire:loading.attr="disabled">Cari Kode</flux:button>
                        <flux:button type="button" x-on:click="start()" x-bind:disabled="starting || scanning">Scan Kamera</flux:button>
                        <flux:button type="button" x-show="scanning" x-cloak x-on:click="stop()">Hentikan Kamera</flux:button>
                    </div>
                </form>
                <div wire:ignore x-show="scanning" x-cloak>
                    <video x-ref="video" autoplay muted playsinline class="w-full max-w-md rounded-lg" aria-label="Pratinjau scan QR"></video>
                </div>
                <p x-text="message" role="status" class="text-sm"></p>
                <flux:text size="sm">Scanner eksternal: fokuskan kolom kode, pindai, lalu Enter. Pemilik dan lokasi mengikuti stok barang secara otomatis.</flux:text>
                <flux:heading size="md">Tambah barang ke keranjang</flux:heading>
                <div class="grid gap-3 md:grid-cols-2">
                    <flux:select label="Jenis barang" wire:model.live="itemId">
                        <flux:select.option value="">Pilih barang</flux:select.option>
                        @foreach ($items as $item)
                            <flux:select.option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->name }} ({{ $item->tracking_type === 'individual' ? 'per unit' : 'stok' }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input label="Jumlah" type="number" min="0.001" step="0.001" wire:model="quantity" />
                    @if ($items->firstWhere('id', (int) $itemId)?->tracking_type === 'individual')
                        <flux:select label="Kode unit aset" wire:model.live="assetId">
                            <flux:select.option value="">Otomatis pilih unit tersedia</flux:select.option>
                            @foreach ($availableAssets as $asset)
                                <flux:select.option value="{{ $asset->id }}">{{ $asset->asset_code }} — {{ $asset->owner?->display_name }} / {{ $asset->location?->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>
                <flux:button type="button" variant="outline" icon="plus" wire:click="addToCart">Tambahkan ke Keranjang</flux:button>
            </div>
            <div class="space-y-2">
                <flux:heading size="md">Keranjang ({{ count($cart) }} baris)</flux:heading>
                @forelse ($cart as $index => $line)
                    <div wire:key="loan-cart-{{ $line['key'] }}" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3">
                        <div>
                            <div class="font-medium">{{ $line['name'] }}</div>
                            <div class="text-xs text-zinc-500">{{ $line['asset_code'] ?: 'Stok kuantitas' }} · {{ number_format((float) $line['quantity'], 3) }} unit · {{ $line['owner_name'] ?? $line['owner_id'] }} · {{ $line['location_name'] ?? $line['location_id'] }}</div>
                        </div>
                        <flux:button size="sm" variant="ghost" type="button" wire:click="removeFromCart({{ $index }})">Hapus</flux:button>
                    </div>
                @empty
                    <flux:text size="sm">Keranjang masih kosong.</flux:text>
                @endforelse
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button variant="primary" type="button" wire:click="requestLoan" :disabled="count($cart) === 0">Simpan Permohonan</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showRequestForm', false)">Batal</flux:button>
            </div>
        </flux:card>
    @endif

    @if ($activeLoan && $transactionMode !== '')
        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ $transactionMode === 'handover' ? 'Catat Serah-Terima' : 'Catat Pengembalian' }} — {{ $activeLoan->loan_number }}</flux:heading>
            <form wire:submit="saveTransaction" class="grid gap-4 md:grid-cols-2">
                <flux:select label="Barang" wire:model="activeLoanItemId" required>
                    <flux:select.option value="">Pilih barang dalam peminjaman</flux:select.option>
                    @foreach ($activeLoan->items as $line)
                        <flux:select.option value="{{ $line->id }}">{{ $line->item?->name }} — diminta {{ $line->quantity_requested }}, diserahkan {{ $line->quantity_handed_over }}, kembali {{ $line->quantity_returned }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select label="Metode pencatatan" wire:model.live="recordingMethod">
                    <flux:select.option value="online">Online</flux:select.option>
                    <flux:select.option value="offline">Dicatat setelah transaksi offline</flux:select.option>
                </flux:select>
                @if ($transactionMode === 'handover')
                    <flux:input label="Jumlah diserahkan" type="number" min="0.001" step="0.001" wire:model="handoverQuantity" required />
                @else
                    <flux:input label="Jumlah dikembalikan" type="number" min="0" step="0.001" wire:model="returnQuantity" required />
                    <flux:input label="Jumlah hilang" type="number" min="0" step="0.001" wire:model="missingQuantity" required />
                    <flux:select label="Kondisi barang kembali" wire:model="returnCondition">
                        <flux:select.option value="good">Baik</flux:select.option>
                        <flux:select.option value="damaged">Rusak</flux:select.option>
                    </flux:select>
                @endif
                @if ($recordingMethod === 'offline')
                    <flux:input label="Waktu kejadian fisik (WITA sesuai zona aplikasi)" type="datetime-local" wire:model="occurredAt" required />
                    <flux:text size="sm" class="md:col-span-2">Isi waktu kejadian sebenarnya. Waktu pencatatan sistem disimpan terpisah untuk audit.</flux:text>
                @endif
                <flux:textarea label="Catatan" wire:model="transactionNotes" />
                <div class="flex gap-2 md:col-span-2">
                    <flux:button type="submit" variant="primary" wire:confirm="Pastikan jumlah dan kondisi sudah sesuai pemeriksaan fisik. Simpan transaksi?">Simpan Transaksi</flux:button>
                    <flux:button type="button" variant="ghost" wire:click="$set('transactionMode', '')">Batal</flux:button>
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
                                @can('inventory.handover.process')
                                    @if (in_array($loan->status, ['approved', 'partially_handed_over'], true))
                                        <flux:button size="sm" variant="outline" wire:click="openTransaction({{ $loan->id }}, 'handover')">Serah-terima</flux:button>
                                    @endif
                                @endcan
                                @can('inventory.returns.process')
                                    @if (in_array($loan->status, ['handed_over', 'partially_handed_over', 'partially_returned', 'discrepancy'], true))
                                        <flux:button size="sm" variant="outline" wire:click="openTransaction({{ $loan->id }}, 'return')">Pengembalian</flux:button>
                                    @endif
                                @endcan
                                @foreach (['handover' => 'Serah-terima', 'return' => 'Pengembalian'] as $documentType => $documentLabel)
                                    @php($issuedDocument = $loan->documents->first(fn ($document) => $document->document_type === $documentType && $document->status === 'issued'))
                                    @if ($issuedDocument)
                                        <a class="inline-block rounded border px-2 py-1 text-xs underline" href="{{ route('inventory.loans.documents.show', ['document' => $issuedDocument->id]) }}" target="_blank" rel="noopener">BA Terbit: {{ $documentLabel }}</a>
                                        @can('inventory.documents.correct')
                                            <form method="POST" action="{{ route('inventory.loans.documents.correct', ['document' => $issuedDocument->id]) }}" class="mt-1 flex flex-wrap items-center gap-1">
                                                @csrf
                                                <input name="reason" minlength="10" maxlength="2000" required placeholder="Alasan koreksi (min. 10 karakter)" aria-label="Alasan koreksi {{ $documentLabel }}" class="rounded border px-2 py-1 text-xs dark:bg-zinc-900" />
                                                <button type="submit" class="rounded border px-2 py-1 text-xs" onclick="return confirm('Terbitkan BA versi koreksi? Versi sebelumnya akan tetap tersimpan sebagai arsip.')">Koreksi BA</button>
                                            </form>
                                        @endcan
                                        @can('inventory.documents.reprint')
                                            <form method="POST" action="{{ route('inventory.loans.documents.reprint', ['document' => $issuedDocument->id]) }}" class="mt-1 flex flex-wrap items-center gap-1">
                                                @csrf
                                                <input name="reason" maxlength="500" required placeholder="Alasan cetak ulang" aria-label="Alasan cetak ulang {{ $documentLabel }}" class="rounded border px-2 py-1 text-xs dark:bg-zinc-900" />
                                                <button type="submit" class="rounded border px-2 py-1 text-xs">Catat cetak ulang</button>
                                            </form>
                                        @endcan
                                    @elseif ($loan->events->contains('event_type', $documentType))
                                        @can('inventory.documents.reprint')
                                            <form method="POST" action="{{ route('inventory.loans.documents.issue', ['loan' => $loan->id, 'type' => $documentType]) }}" class="inline-block">
                                                @csrf
                                                <button type="submit" class="rounded border px-2 py-1 text-xs" onclick="return confirm('Terbitkan snapshot BA dari transaksi saat ini? Dokumen tidak dapat diubah langsung.')">Terbitkan BA {{ $documentLabel }}</button>
                                            </form>
                                        @endcan
                                    @endif
                                @endforeach
                                @if ($loan->documents->isNotEmpty())
                                    <details class="mt-2 text-xs">
                                        <summary class="cursor-pointer">Audit dokumen BA ({{ $loan->documents->count() }} versi)</summary>
                                        <div class="mt-2 space-y-2">
                                            @foreach ($loan->documents->sortByDesc('id') as $historyDocument)
                                                <div class="rounded border p-2" wire:key="ba-history-{{ $historyDocument->id }}">
                                                    <a class="underline" target="_blank" rel="noopener" href="{{ route('inventory.loans.documents.show', ['document' => $historyDocument->id]) }}">{{ $historyDocument->document_number }} — v{{ $historyDocument->version }}</a>
                                                    <span>({{ $historyDocument->status === 'issued' ? 'Aktif' : 'Arsip' }})</span>
                                                    <div>Jenis: {{ $historyDocument->document_type === 'handover' ? 'Serah-terima' : 'Pengembalian' }}</div>
                                                    <div>Penerbit: {{ $historyDocument->issuer?->name ?? 'Akun tidak tersedia' }} | {{ $historyDocument->issued_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                                    @if ($historyDocument->correction_reason)
                                                        <div>Alasan koreksi: {{ $historyDocument->correction_reason }}</div>
                                                    @endif
                                                    <div>SHA-256: <span class="font-mono break-all">{{ $historyDocument->sha256 ?? '-' }}</span></div>
                                                    @if ($historyDocument->prints->isNotEmpty())
                                                        <details class="mt-1">
                                                            <summary class="cursor-pointer">Catatan cetak ulang ({{ $historyDocument->prints->count() }})</summary>
                                                            @foreach ($historyDocument->prints->sortByDesc('printed_at') as $print)
                                                                <div class="mt-1"> {{ $print->printed_at?->format('d/m/Y H:i') ?? '-' }} — {{ $print->printer?->name ?? 'Akun tidak tersedia' }} — {{ $print->reason ?: '-' }}</div>
                                                            @endforeach
                                                        </details>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                                @if ($loan->events->contains('event_type', 'handover'))
                                    <a class="inline-block rounded border px-2 py-1 text-xs underline" href="{{ route('inventory.loans.documents.preview', ['loan' => $loan->id, 'type' => 'handover']) }}" target="_blank" rel="noopener">Draft BA Serah-terima</a>
                                @endif
                                @if ($loan->events->contains('event_type', 'return'))
                                    <a class="inline-block rounded border px-2 py-1 text-xs underline" href="{{ route('inventory.loans.documents.preview', ['loan' => $loan->id, 'type' => 'return']) }}" target="_blank" rel="noopener">Draft BA Pengembalian</a>
                                @endif
                            </td>
                        </tr>
                        @if ($loan->events->isNotEmpty())
                            <tr wire:key="loan-events-{{ $loan->id }}" class="border-b bg-zinc-50/50 dark:bg-zinc-900/30">
                                <td colspan="5" class="px-3 py-2">
                                    <details>
                                        <summary class="cursor-pointer text-xs font-medium">Riwayat transaksi ({{ $loan->events->count() }})</summary>
                                        <div class="mt-2 overflow-x-auto">
                                            <table class="w-full text-xs">
                                                <thead><tr><th class="p-2 text-left">Kejadian</th><th class="p-2 text-left">Dicatat</th><th class="p-2 text-left">Jenis</th><th class="p-2 text-left">Jumlah</th><th class="p-2 text-left">Kondisi</th><th class="p-2 text-left">Metode</th><th class="p-2 text-left">Catatan</th></tr></thead>
                                                <tbody>
                                                    @foreach ($loan->events->sortBy('recorded_at') as $event)
                                                        <tr class="border-t" wire:key="loan-event-{{ $event->id }}">
                                                            <td class="p-2">{{ $event->occurred_at?->format('d/m/Y H:i') }}</td>
                                                            <td class="p-2">{{ $event->recorded_at?->format('d/m/Y H:i') }}</td>
                                                            <td class="p-2">{{ ['handover' => 'Serah-terima', 'return' => 'Pengembalian', 'loss' => 'Hilang', 'damage' => 'Rusak', 'offline_entry' => 'Entri offline'][$event->event_type] ?? $event->event_type }}</td>
                                                            <td class="p-2">{{ $event->quantity ?? '-' }}</td>
                                                            <td class="p-2">{{ $event->condition ?? '-' }}</td>
                                                            <td class="p-2">{{ $event->recording_method }}</td>
                                                            <td class="p-2">{{ $event->notes ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="px-3 py-8 text-center text-zinc-500">Belum ada permohonan peminjaman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $loans->links() }}
    </flux:card>
</div>
