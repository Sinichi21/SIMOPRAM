<h1 style="text-align:center;font-size:15pt;">BUKU KAS GUGUSDEPAN</h1>
<p style="text-align:center;">{{ $gudep->name }} — Tahun Ajaran {{ $year->name }}</p>
<p style="font-size:8pt;">Hanya transaksi terposting; mata uang Rupiah. Arsip terbatas dengan QR verifikasi.</p>
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
<tr><td>{{ $loop->iteration }}</td><td>{{ $row->occurred_at?->format('d/m/Y') }}</td><td>{{ $row->description }}</td><td>{{ $row->category }}</td><td>{{ $row->direction === 'income' ? number_format($row->amount, 0, ',', '.') : '-' }}</td><td>{{ $row->direction === 'expense' ? number_format($row->amount, 0, ',', '.') : '-' }}</td><td>{{ $row->reference_number ?: '-' }}
@foreach ($row->evidences as $evidence)
<div style="font-size:7pt;">Bukti: {{ $evidence->original_filename }} (SHA-256: {{ substr($evidence->sha256, 0, 12) }}…)</div>
@endforeach
</td></tr>
@empty<tr><td colspan="7">Belum ada transaksi terposting.</td></tr>@endforelse
</tbody></table>
<p>Total penerimaan: Rp {{ number_format($income, 0, ',', '.') }}</p>
<p>Total pengeluaran: Rp {{ number_format($expense, 0, ',', '.') }}</p>
<p>Saldo buku kas: Rp {{ number_format($income - $expense, 0, ',', '.') }}</p>
<p style="font-size:8pt;">Realisasi menggunakan pengeluaran terposting sesuai kategori anggaran. Bukti asli disimpan privat dan hanya dapat diunduh pengguna berizin. Belum termasuk saldo awal, koreksi akuntansi, atau rekonsiliasi kas fisik.</p>
