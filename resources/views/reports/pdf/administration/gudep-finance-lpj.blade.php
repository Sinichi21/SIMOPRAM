<h1 style="text-align:center;font-size:15pt;">LAPORAN PERTANGGUNGJAWABAN KEUANGAN GUDEP</h1>
<p style="text-align:center;">{{ $gudep->name }} — Tahun Ajaran {{ $year->name }}</p>
<p style="font-size:8pt;">Arsip resmi terbatas. Seluruh nominal dalam Rupiah. Laporan mencakup transaksi yang sudah diposting dan pembalikan yang terdokumentasi.</p>
<h2 style="font-size:11pt;">A. Ringkasan Kas</h2>
<table style="width:100%;border-collapse:collapse;font-size:9pt;" border="1" cellpadding="6">
<tr><td>Saldo awal</td><td align="right">Rp {{ number_format($openingBalance, 0, ',', '.') }}</td></tr>
<tr><td>Penerimaan bersih</td><td align="right">Rp {{ number_format($income, 0, ',', '.') }}</td></tr>
<tr><td>Pengeluaran bersih</td><td align="right">Rp {{ number_format($expense, 0, ',', '.') }}</td></tr>
<tr><td><strong>Saldo akhir / berjalan</strong></td><td align="right"><strong>Rp {{ number_format($openingBalance + $income - $expense, 0, ',', '.') }}</strong></td></tr>
</table>
<h2 style="font-size:11pt;">B. Realisasi Anggaran</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
<thead><tr><th>Kategori</th><th>Anggaran (Rp)</th><th>Realisasi (Rp)</th><th>Selisih (Rp)</th></tr></thead>
<tbody>
@forelse ($budgets as $budget)
@php $realized = (int) ($expenseByCategory[$budget->category] ?? 0); @endphp
<tr><td>{{ $budget->category }}</td><td align="right">{{ number_format($budget->allocated_amount, 0, ',', '.') }}</td><td align="right">{{ number_format($realized, 0, ',', '.') }}</td><td align="right">{{ number_format($budget->allocated_amount - $realized, 0, ',', '.') }}</td></tr>
@empty
<tr><td colspan="4">Belum ada anggaran yang dialokasikan.</td></tr>
@endforelse
</tbody>
</table>
<h2 style="font-size:11pt;">C. Bukti Pendukung</h2>
<p>Transaksi terposting: {{ $entries->count() }} baris (termasuk entri pembalikan). Bukti privat terlampir secara elektronik: {{ $evidenceCount }} berkas.</p>
<p style="font-size:8pt;">Bukti asli dapat diperiksa melalui sistem SIMPRAM sesuai hak akses pengguna. Arsip PDF ini tidak membuka isi bukti privat.</p>
<h2 style="font-size:11pt;">D. Rekonsiliasi dan Persetujuan</h2>
<p>Status periode: {{ $period?->status === 'closed' ? 'Ditutup' : ($period?->close_requested_at ? 'Menunggu persetujuan penutupan' : 'Terbuka') }}.</p>
@if ($reconciliation)
<p>Rekonsiliasi terakhir: saldo buku Rp {{ number_format($reconciliation->book_balance, 0, ',', '.') }}; kas fisik Rp {{ number_format($reconciliation->counted_cash, 0, ',', '.') }}; selisih Rp {{ number_format($reconciliation->difference, 0, ',', '.') }}.</p>
@else
<p>Rekonsiliasi kas fisik belum tercatat.</p>
@endif
@if ($period?->status === 'closed')
<p>Permohonan penutupan: pengguna #{{ $period->close_requested_by }}. Persetujuan: pengguna #{{ $period->closed_by }} pada {{ $period->closed_at?->format('d/m/Y H:i') }}.</p>
<p style="font-size:7pt;">Checksum penutupan: {{ $period->closing_checksum }}</p>
@endif
<h2 style="font-size:11pt;">E. Catatan Pertanggungjawaban</h2>
<p style="font-size:8pt;">Realisasi hanya mengurangi kategori yang sama persis. Kategori tanpa alokasi anggaran tetap termasuk dalam total pengeluaran. Dokumen ini merupakan rekap keuangan, bukan pengganti pemeriksaan kuitansi asli dan pertanggungjawaban kegiatan.</p>
