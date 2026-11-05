<h1 style="text-align:center;font-size:15pt;">BUKU KAS GUGUSDEPAN</h1>
<p style="text-align:center;">{{ $gudep->name }} — Tahun Ajaran {{ $year->name }}</p>
<p style="font-size:8pt;"><strong>Status buku kas:</strong> {{ $period?->status === 'closed' ? 'Ditutup' : ($period?->close_requested_at ? 'Menunggu persetujuan tutup buku' : 'Terbuka / Belum ditutup') }}.</p>
<p style="font-size:8pt;">Hanya transaksi terposting; mata uang Rupiah. Pembalikan dicatat sebagai pengurang kategori semula, bukan pendapatan atau biaya baru. Arsip terbatas dengan QR verifikasi.</p>
<h2 style="font-size:11pt;">A. Anggaran</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
<thead><tr><th>Kategori</th><th>Alokasi (Rp)</th><th>Realisasi (Rp)</th><th>Sisa / Selisih (Rp)</th></tr></thead><tbody>
@forelse ($budgets as $row)
@php
    $realized = (int) ($expenseByCategory[$row->category] ?? 0);
@endphp
<tr><td>{{ $row->category }}</td><td align="right">{{ number_format($row->allocated_amount, 0, ',', '.') }}</td><td align="right">{{ number_format($realized, 0, ',', '.') }}</td><td align="right">{{ number_format($row->allocated_amount - $realized, 0, ',', '.') }}</td></tr>
@empty<tr><td colspan="4">Belum ada anggaran.</td></tr>@endforelse
</tbody></table>
<h2 style="font-size:11pt;">B. Transaksi Buku Kas</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
<thead><tr><th>No.</th><th>Tanggal</th><th>Uraian</th><th>Kategori</th><th>Masuk</th><th>Keluar</th><th>No. Bukti</th></tr></thead><tbody>
@forelse ($entries as $row)
<tr><td>{{ $loop->iteration }}</td><td>{{ $row->occurred_at?->format('d/m/Y') }}</td><td>{{ $row->description }}</td><td>{{ $row->category }}</td><td>{{ $row->reversal_of_id !== null ? ($row->direction === 'expense' ? '-'.number_format($row->amount, 0, ',', '.') : '-') : ($row->direction === 'income' ? number_format($row->amount, 0, ',', '.') : '-') }}</td><td>{{ $row->reversal_of_id !== null ? ($row->direction === 'income' ? '-'.number_format($row->amount, 0, ',', '.') : '-') : ($row->direction === 'expense' ? number_format($row->amount, 0, ',', '.') : '-') }}</td><td>{{ $row->reference_number ?: '-' }}
@if ($row->reversal_of_id !== null)
<div style="font-size:7pt;">Pembalikan transaksi #{{ $row->reversal_of_id }}. Alasan: {{ $row->reversal_reason }}</div>
@endif
@if ($row->inventoryReceipt)
<div style="font-size:7pt;">Penerimaan inventaris: pergerakan #{{ $row->inventoryReceipt->inventory_stock_movement_id }}@foreach ($row->inventoryReceipt->additionalItems as $extra) · #{{ $extra->inventory_stock_movement_id }}@endforeach</div>
@foreach ($row->inventoryReceipt->corrections as $correction)
<div style="font-size:7pt;">Koreksi stok: #{{ $correction->original_stock_movement_id }} → #{{ $correction->correction_stock_movement_id }} ({{ $correction->quantity }}), {{ $correction->reason }}</div>
@endforeach
@endif
@foreach ($row->evidences as $evidence)
<div style="font-size:7pt;">Bukti: {{ $evidence->original_filename }} (SHA-256: {{ substr($evidence->sha256, 0, 12) }}…)</div>
@endforeach
</td></tr>
@empty<tr><td colspan="7">Belum ada transaksi terposting.</td></tr>@endforelse
</tbody></table>
<p>Saldo awal: Rp {{ number_format($openingBalance, 0, ',', '.') }}</p>
@if ($reconciliation)
<p>Rekonsiliasi terakhir: saldo buku Rp {{ number_format($reconciliation->book_balance, 0, ',', '.') }}, kas fisik Rp {{ number_format($reconciliation->counted_cash, 0, ',', '.') }}, selisih Rp {{ number_format($reconciliation->difference, 0, ',', '.') }}</p>
<p style="font-size:7pt;">Rekonsiliasi ID #{{ $reconciliation->id }}, checksum buku {{ $reconciliation->ledger_checksum }}</p>
@endif
<p>Total penerimaan: Rp {{ number_format($income, 0, ',', '.') }}</p>
<p>Total pengeluaran: Rp {{ number_format($expense, 0, ',', '.') }}</p>
<p>Saldo berjalan: Rp {{ number_format($openingBalance + $income - $expense, 0, ',', '.') }}</p>
@if ($period?->status === 'closed')
<p><strong>Periode ditutup:</strong> {{ $period->closed_at?->format('d/m/Y H:i') }}</p>
<p style="font-size:8pt;">Diajukan oleh pengguna #{{ $period->close_requested_by }} pada {{ $period->close_requested_at?->format('d/m/Y H:i') }}. Disetujui oleh pengguna #{{ $period->closed_by }} pada {{ $period->closed_at?->format('d/m/Y H:i') }}.</p>
<p style="font-size:7pt;">Checksum penutupan: {{ $period->closing_checksum }}</p>
<p style="font-size:8pt;">Catatan: {{ $period->closing_notes }}</p>
@endif
<p style="font-size:8pt;">Realisasi menggunakan pengeluaran terposting dan pembalikan, dengan pencocokan nama kategori tanpa membedakan huruf besar/kecil. Bukti asli disimpan privat dan hanya dapat diunduh pengguna berizin. Saldo sudah mencakup saldo awal; rekonsiliasi kas fisik belum otomatis dilakukan.</p>
