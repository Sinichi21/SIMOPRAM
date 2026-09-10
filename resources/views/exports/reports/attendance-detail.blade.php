<table><tr><th>{{ $schoolName }}</th></tr><tr><td>{{ $periodLabel }}</td></tr></table>
<table><tr><th>Rekap Absensi Detail - {{ $activityLabel }}</th></tr><tr><td>H: hadir; T: terlambat; S: sakit; I: izin; A: alpa; ?: belum diabsen; -: bukan peserta</td></tr></table>
@include('reports.partials.attendance-detail-table')
