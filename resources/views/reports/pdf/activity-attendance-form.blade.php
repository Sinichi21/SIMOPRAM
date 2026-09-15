<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>{{ $blank ? 'Form' : 'Rekap' }} absensi</title>
<style>
    @page { margin: 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
    h1 { text-align: center; font-size: 17px; margin-bottom: 5px; }
    h2 { text-align: center; font-size: 13px; margin-top: 0; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 15px; }
    th, td { border: 1px solid #555; padding: 7px; word-wrap: break-word; }
    th { background: #eee; } thead { display: table-header-group; } tr { page-break-inside: avoid; }
    .signature { height: 30px; } .footer { margin-top: 25px; text-align: right; }
</style></head><body>
<h1>{{ $blank ? 'FORM DAFTAR HADIR' : 'REKAP ABSENSI' }}</h1><h2>{{ $activity->title }}</h2>
<p>{{ $sessionName }}<br>Waktu kegiatan: {{ $activity->start_at->format('d-m-Y H:i') }} – {{ $activity->end_at?->format('d-m-Y H:i') }} ({{ config('app.timezone') }})<br>Lokasi: {{ $activity->location ?: '—' }}</p>
<p>Jumlah pada cetakan: {{ $rows->count() }} orang. {{ $blank ? 'Tanggal pelaksanaan: ................................' : 'Dicetak: '.now()->format('d-m-Y H:i') }}</p>
<table><thead><tr><th style="width:4%">No.</th><th style="width:19%">Nama</th><th style="width:12%">NTA / NIP / NIS</th><th style="width:16%">Kelompok / entri</th><th style="width:16%">Sekolah asal</th><th style="width:10%">Peran</th><th>{{ $blank ? 'Tanda tangan' : 'Kehadiran / waktu hadir' }}</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $loop->iteration }}</td><td>{{ $row['name'] }}</td><td>{{ $row['identifier'] ?: '—' }}</td><td>{{ $row['group'] }}</td><td>{{ $row['school'] }}</td><td>{{ $row['role'] }}</td><td class="signature">@unless($blank){{ $row['status'] }}@if($row['time'])<br>{{ $row['time'] }}@endif@endunless</td></tr>@empty<tr><td colspan="7">Tidak ada peserta sesuai pilihan.</td></tr>@endforelse
</tbody></table>
<div class="footer">Petugas / penanggung jawab<br><br><br><br>(........................................)</div>
</body></html>
