<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>LPJ Pramuka</title>
    <style>
        @page { margin: 20mm 18mm; } body { color: #111; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.5; }
        h1,h2,h3,p { margin-top: 0; } h1 { font-size: 19px; } h2 { font-size: 15px; } h3 { font-size: 12px; margin-bottom: 5px; }
        .center { text-align: center; } .page-break { page-break-after: always; } .cover { padding-top: 55mm; text-align: center; } .cover .school { margin-top: 50mm; }
        .approval { padding-top: 15mm; } .approval-title { margin-bottom: 25mm; text-align: center; } .signature { margin-top: 28mm; width: 100%; }
        .signature td { text-align: center; vertical-align: top; width: 50%; } .signature-space { height: 22mm; }
        .meta,.summary { border-collapse: collapse; margin-bottom: 15px; width: 100%; } .meta td { padding: 2px 4px; } .meta td:first-child { width: 125px; }
        .summary th,.summary td { border: 1px solid #555; padding: 5px; text-align: center; } .summary th { background: #eee; }
        .month-title { border-bottom: 2px solid #222; margin: 18px 0 10px; padding-bottom: 4px; } .activity { border: 1px solid #888; margin-bottom: 12px; padding: 10px; page-break-inside: avoid; }
        .activity-table { width: 100%; } .activity-table td { padding: 2px; vertical-align: top; } .activity-table td:first-child { width: 105px; }
        .journal { margin-top: 8px; } .journal strong { display: block; } .photos { margin-top: 8px; }
        .photo { display: inline-block; margin: 3px; text-align: center; vertical-align: top; width: 45%; } .photo img { height: 115px; max-width: 100%; object-fit: contain; }
        .empty { border: 1px dashed #999; color: #666; padding: 20px; text-align: center; } .footer-note { color: #666; font-size: 8px; margin-top: 20px; }
    </style>
</head>
<body>
@if ($periodType === 'semester')
    <section class="cover page-break">
        <h1>LAPORAN PERTANGGUNGJAWABAN</h1><h2>KEGIATAN EKSTRAKURIKULER PRAMUKA</h2>
        <p>{{ strtoupper($semester->name) }} — TAHUN AJARAN {{ $academicYear->name }}</p>
        <div class="school"><h2>{{ strtoupper($school->name) }}</h2>
            @if ($documentSetting?->gudep_male_number || $documentSetting?->gudep_female_number)
                <p>Gugus Depan {{ collect([$documentSetting?->gudep_male_number, $documentSetting?->gudep_female_number])->filter()->implode(' / ') }}</p>
            @endif
            <p>{{ $scoutGroup?->secretariat_address ?? $school->address }}</p>
        </div>
    </section>
    <section class="approval page-break">
        <div class="approval-title"><h1>LEMBAR PENGESAHAN</h1><h2>LAPORAN PERTANGGUNGJAWABAN KEGIATAN PRAMUKA</h2><p>{{ $semester->name }} Tahun Ajaran {{ $academicYear->name }}</p></div>
        <p>Laporan kegiatan ekstrakurikuler Pramuka {{ $school->name }} ini telah diperiksa dan disahkan sebagai pertanggungjawaban pelaksanaan kegiatan selama {{ $semester->name }}.</p>
        <table class="signature">
            <tr><td>Mengetahui,<br>Kepala Sekolah/Kamabigus</td><td>{{ $documentSetting?->signing_city ?? '................' }}, {{ now()->translatedFormat('d F Y') }}<br>Pembina Pramuka</td></tr>
            <tr><td class="signature-space"></td><td></td></tr>
            <tr><td><strong>{{ $documentSetting?->principal_name ?? $scoutGroup?->kamabigus_name ?? '........................' }}</strong><br>NIP. {{ $documentSetting?->principal_nip ?? '........................' }}</td>
                <td><strong>{{ $documentSetting?->responsibleCoach?->name ?? $scoutGroup?->head_coach_name ?? '........................' }}</strong><br>{{ $documentSetting?->responsibleCoach?->nip ? 'NIP. '.$documentSetting->responsibleCoach->nip : '' }}</td></tr>
        </table>
    </section>
@endif
<header class="center"><h1>LAPORAN KEGIATAN PRAMUKA</h1><h2>{{ $school->name }}</h2><p>{{ $periodType === 'monthly' ? $periodStart->translatedFormat('F Y') : $semester->name.' Tahun Ajaran '.$academicYear->name }}</p></header>
<table class="meta"><tr><td>Tahun Ajaran</td><td>: {{ $academicYear->name }}</td></tr><tr><td>Semester</td><td>: {{ $semester->name }}</td></tr><tr><td>Periode</td><td>: {{ $periodStart->translatedFormat('d F Y') }} s.d. {{ $periodEnd->translatedFormat('d F Y') }}</td></tr></table>
<table class="summary"><tr><th>Kegiatan</th><th>Sesi Absensi Aktif</th><th>Peserta</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpa</th></tr>
<tr><td>{{ $activities->count() }}</td><td>{{ $attendance['sessions'] }}</td><td>{{ $attendance['participants'] }}</td><td>{{ $attendance['present'] }}</td><td>{{ $attendance['sick'] }}</td><td>{{ $attendance['excused'] }}</td><td>{{ $attendance['absent'] }}</td></tr></table>
@forelse ($activitiesByMonth as $monthKey => $monthActivities)
    <h2 class="month-title">{{ \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->translatedFormat('F Y') }}</h2>
    @foreach ($monthActivities as $activity)
        <article class="activity"><h3>{{ $loop->iteration }}. {{ $activity->title }}</h3>
            <table class="activity-table"><tr><td>Waktu</td><td>: {{ $activity->start_at->translatedFormat('l, d F Y H:i') }}{{ $activity->end_at ? ' – '.$activity->end_at->format('H:i') : '' }}</td></tr>
                <tr><td>Tempat</td><td>: {{ $activity->location ?: '-' }}</td></tr><tr><td>Pembina</td><td>: {{ $activity->coaches->pluck('name')->implode(', ') ?: '-' }}</td></tr>
                <tr><td>Golongan</td><td>: {{ $activity->scoutLevels->pluck('name')->implode(', ') ?: '-' }}</td></tr><tr><td>Sesi Absensi</td><td>: {{ $activity->attendanceSessions->count() }} sesi aktif</td></tr></table>
            @if ($activity->description)<div class="journal"><strong>Deskripsi Kegiatan</strong>{{ $activity->description }}</div>@endif
            @if ($activity->journal)
                @foreach (['objective' => 'Tujuan', 'material' => 'Materi', 'activity_description' => 'Pelaksanaan', 'result' => 'Hasil', 'evaluation' => 'Evaluasi', 'follow_up' => 'Tindak Lanjut', 'notes' => 'Catatan'] as $field => $label)
                    @if ($activity->journal->{$field})<div class="journal"><strong>{{ $label }}</strong>{!! nl2br(e($activity->journal->{$field})) !!}</div>@endif
                @endforeach
                @if ($activity->journal->attachments->isNotEmpty())
                    <div class="photos"><strong>Dokumentasi</strong><br>@foreach ($activity->journal->attachments as $attachment)
                        @if ($attachment->pdf_path)<div class="photo"><img src="{{ $attachment->pdf_path }}"><br>{{ $attachment->original_name }}</div>@else <div>{{ $attachment->original_name }}</div>@endif
                    @endforeach</div>
                @endif
            @endif
        </article>
    @endforeach
@empty
    <div class="empty">Belum ada kegiatan berstatus terbit atau selesai pada periode ini.</div>
@endforelse
<p class="footer-note">Dokumen dibuat oleh SIMPRAM pada {{ now()->translatedFormat('d F Y H:i') }}. Bagian keuangan tidak termasuk dalam laporan ini.</p>
</body></html>
