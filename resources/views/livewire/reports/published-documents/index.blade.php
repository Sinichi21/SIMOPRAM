<div class="space-y-6">
    <flux:error name="document" />
    @if (session('status'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300">
            {{ session('status') }}
        </div>
    @endif

    <div>
        <h1 class="text-2xl font-semibold">Dokumen Terbit</h1>
        <p class="mt-1 text-sm text-zinc-500">Registri dokumen resmi SIMPRAM: surat, rekap, LPJ, dan dokumen lain yang memiliki PDF arsip serta QR verifikasi.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['Total', $statistics['total']],
            ['Valid', $statistics['valid']],
            ['Versi Lama', $statistics['superseded']],
            ['Dicabut', $statistics['revoked']],
            ['Total Verifikasi', $statistics['verification_count']],
        ] as [$label, $value])
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500">{{ $label }}</div>
                <div class="mt-1 text-xl font-semibold">{{ number_format($value) }}</div>
            </div>
        @endforeach
    </div>

    <section class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-3 border-b border-zinc-200 p-4 md:grid-cols-5 dark:border-zinc-800">
            <input wire:model.live.debounce.300ms="search" placeholder="Nomor, judul, kode, hash..." class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
            <select wire:model.live="documentType" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">Semua jenis</option>
                @foreach ($documentTypes as $type)
                    <option value="{{ $type }}">{{ match ($type) { 'letter' => 'Surat Keluar', 'grades' => 'Rekap Nilai', 'attendance' => 'Rekap Absensi', 'lpj' => 'LPJ Kegiatan', default => str($type)->replace('_', ' ')->title() } }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">Semua status</option>
                <option value="valid">Valid</option>
                <option value="pending">Menunggu persetujuan</option>
                <option value="superseded">Versi Lama</option>
                <option value="revoked">Dicabut</option>
            </select>
            <input type="date" wire:model.live="dateFrom" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
            <input type="date" wire:model.live="dateTo" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-zinc-950/60">
                    <tr>
                        <th class="px-4 py-3">Dokumen</th>
                        <th class="px-4 py-3">Nomor / Periode</th>
                        <th class="px-4 py-3">Diterbitkan</th>
                        <th class="px-4 py-3">Verifikasi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($documents as $document)
                        @php
                            $closure = $document->closure;
                            $publicStatus = $document->publicStatus();
                        @endphp
                        <tr>
                            <td class="px-4 py-4 align-top">
                                <div class="font-medium">{{ $document->documentTypeLabel() }}</div>
                                <div class="mt-1 max-w-sm text-xs text-zinc-500">{{ $document->publicTitle() }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                @if ($document->document_number)
                                    <div class="font-medium">{{ $document->document_number }}</div>
                                @elseif ($closure)
                                    <div>{{ $closure->academicYear?->name ?? '-' }} · {{ $closure->semester?->name ?? '-' }}</div>
                                    <div class="text-xs text-zinc-500">Snapshot v{{ $closure->version ?? '-' }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top">
                                {{ $document->issued_at?->format('d/m/Y H:i') ?? '-' }}
                                <div class="mt-1 text-xs text-zinc-500">{{ $document->issuer?->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <code class="block max-w-[180px] truncate text-xs" title="{{ $document->code }}">{{ $document->code }}</code>
                                <div class="mt-1 text-xs text-zinc-500">{{ number_format($document->verification_count) }} kali</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                @if ($publicStatus === 'valid')
                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-300">Valid</span>
                                @elseif ($publicStatus === 'pending')
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-300">Menunggu persetujuan</span>
                                @elseif ($publicStatus === 'superseded')
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-300">Versi Lama</span>
                                @else
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-950 dark:text-red-300">Dicabut</span>
                                    @if ($document->revocation_reason)
                                        <div class="mt-2 max-w-xs text-xs text-zinc-500">{{ $document->revocation_reason }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right align-top">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @can('documents.approve')
                                        @if ($publicStatus === 'pending' && $document->hasArchivedPdf() && in_array(auth()->id(), $document->pendingSignatoryIds(), true))
                                            <flux:button size="sm" variant="primary" wire:click="approve({{ $document->id }})" wire:confirm="Pastikan Anda telah membaca PDF. Setujui dokumen ini sebagai penandatangan?">Setujui dokumen</flux:button>
                                        @endif
                                    @endcan
                                    <a href="{{ route('reports.published-documents.show', ['code' => $document->code]) }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium dark:border-zinc-700">Detail</a>
                                    @if (! $document->isRevoked() && $document->hasArchivedPdf())
                                        @can('reports.export')
                                            <a href="{{ route('reports.published-documents.download', ['code' => $document->code]) }}" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-medium text-white dark:bg-white dark:text-zinc-900" target="_blank" rel="noopener noreferrer">Lihat PDF</a>
                                        @endcan
                                    @endif
                                    <a href="{{ route('reports.verify', ['code' => $document->code]) }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium dark:border-zinc-700">Verifikasi</a>
                                    @can('report_verifications.manage')
                                        @if (! $document->isRevoked())
                                            <button type="button" wire:click="startRevoke({{ $document->id }})" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 dark:border-red-800 dark:text-red-300">Cabut</button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @if ($revokeId === $document->id)
                            <tr>
                                <td colspan="6" class="bg-red-50/70 px-4 py-4 dark:bg-red-950/20">
                                    <div class="max-w-3xl space-y-3">
                                        <div class="font-medium text-red-800 dark:text-red-300">Cabut Dokumen</div>
                                        <p class="text-xs text-red-700 dark:text-red-400">QR tidak dihapus. Saat dipindai, status dokumen akan menjadi Dicabut agar histori penerbitan tetap dapat diverifikasi.</p>
                                        <textarea wire:model="revocationReason" rows="3" class="w-full rounded-lg border border-red-300 bg-white px-3 py-2 text-sm dark:border-red-800 dark:bg-zinc-950" placeholder="Tuliskan alasan pencabutan dokumen..."></textarea>
                                        @error('revocationReason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                        <div class="flex gap-2">
                                            <button type="button" wire:click="revoke" wire:confirm="Cabut dokumen ini? QR lama akan tetap dapat diverifikasi dengan status Dicabut." class="rounded-lg bg-red-700 px-4 py-2 text-sm font-medium text-white">Konfirmasi Cabut</button>
                                            <button type="button" wire:click="cancelRevoke" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700">Batal</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-zinc-500">Belum ada dokumen terbit yang sesuai dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($documents->hasPages())
            <div class="border-t border-zinc-200 px-4 py-4 dark:border-zinc-800">{{ $documents->links() }}</div>
        @endif
    </section>
</div>
