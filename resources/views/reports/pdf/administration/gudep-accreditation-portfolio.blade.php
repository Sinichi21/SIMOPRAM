<h1 style="text-align:center;font-size:15pt;">PORTOFOLIO AKREDITASI GUDEP</h1>
<p style="text-align:center;">{{ $gudep->name }} — Tahun Ajaran {{ $year->name }}</p>
<p style="font-size:8pt;">Dokumen internal berklasifikasi Terbatas. Ringkasan ini bukan sertifikat, penilaian, atau keputusan akreditasi resmi. Seluruh bukti tetap tersimpan pada modul asal dan hanya dapat diakses sesuai izin pengguna.</p>
<h2 style="font-size:11pt;">A. Ringkasan Kelengkapan Internal</h2>
<table style="width:100%;border-collapse:collapse;font-size:9pt;" border="1" cellpadding="6">
<tr><td>Jumlah indikator</td><td>{{ $indicators->count() }}</td></tr>
<tr><td>Indikator dengan bukti</td><td>{{ $indicators->filter(fn ($indicator) => $indicator->evidenceLinks->isNotEmpty())->count() }}</td></tr>
<tr><td>Ditinjau internal</td><td>{{ $indicators->where('status', 'reviewed')->count() }}</td></tr>
</table>
<h2 style="font-size:11pt;">B. Daftar Indikator dan Referensi Bukti</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
<thead><tr><th>Kode</th><th>Golongan</th><th>Indikator</th><th>Status</th><th>Referensi sumber</th></tr></thead>
<tbody>
@foreach ($rows as $row)
<tr>
<td>{{ $row['code'] }}</td>
<td>{{ $row['level'] }}</td>
<td>{{ $row['title'] }}</td>
<td>{{ $row['status'] }}</td>
<td>
@foreach ($row['references'] as $reference)
{{ $reference['kind'] }} #{{ $reference['id'] }}@if ($reference['code']) ({{ $reference['code'] }})@endif@if (! $loop->last), @endif
@endforeach
</td>
</tr>
@endforeach
</tbody>
</table>
<h2 style="font-size:11pt;">C. Informasi Audit</h2>
<p>Dokumen ini berisi snapshot daftar indikator dan referensi bukti pada saat diterbitkan. Arsip PDF dan kode QR diverifikasi melalui sistem SIMPRAM.</p>
<p style="font-size:8pt;">Perubahan pada indikator atau bukti setelah penerbitan membutuhkan revisi dokumen baru. Referensi dokumen sumber yang dicabut atau telah digantikan tidak dapat digunakan untuk penerbitan baru.</p>
