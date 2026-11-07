@include('administration.partials.gudep-document-letterhead', ['letterhead' => $snapshot['letterhead']])
@if ($snapshot['kind'] === 'semester')
<h1 style="text-align:center;font-size:15pt;margin-bottom:4px;">PROGRAM KERJA {{ ['annual' => 'TAHUNAN', 'semester' => 'SEMESTER', 'weekly' => 'LATIHAN MINGGUAN'][$snapshot['kind']] ?? 'GUGUSDEPAN' }}</h1>
<p style="text-align:center;font-size:10pt;margin-top:0;">{{ $snapshot['gudep_name'] }} — {{ $snapshot['year_name'] }}</p>
<table style="width:100%;font-size:9pt;margin:12px 0;border-collapse:collapse;">
    <tr><td style="width:28%;">Judul program</td><td>: {{ $snapshot['title'] }}</td></tr>
    <tr><td>Nomor Gudep</td><td>: Putra {{ $snapshot['gudep_male_number'] ?: '–' }} / Putri {{ $snapshot['gudep_female_number'] ?: '–' }}</td></tr>
    <tr><td>Tahun ajaran</td><td>: {{ $snapshot['year_name'] }}</td></tr>
    @if ($snapshot['kind'] !== 'annual')
        <tr><td>Semester</td><td>: {{ $snapshot['semester_name'] ?: '–' }}</td></tr>
        <tr><td>Golongan</td><td>: {{ $snapshot['level_name'] ?: '–' }}</td></tr>
    @endif
    @if ($snapshot['kind'] === 'weekly')
        <tr><td>Tanggal latihan</td><td>: {{ $snapshot['scheduled_for'] ?: '–' }}</td></tr>
        <tr><td>Agenda</td><td>: {{ $snapshot['activity_title'] ?: '–' }}</td></tr>
        <tr><td>Lokasi</td><td>: {{ $snapshot['activity_location'] ?: '–' }}</td></tr>
    @endif
</table>
@endif
@if ($snapshot['kind'] === 'annual')
    @include('administration.partials.annual-program-body', [
        'entries' => $snapshot['entries'],
        'kwarcab' => $snapshot['gudep_kwarcab'] ?? '',
        'maleNumber' => $snapshot['gudep_male_number'] ?? '',
        'femaleNumber' => $snapshot['gudep_female_number'] ?? '',
        'yearName' => $snapshot['year_name'],
    ])
@elseif ($snapshot['kind'] === 'semester')
    <h2 style="font-size:10pt;">Rencana Kegiatan Semester</h2>
    <table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="4">
        <thead><tr><th>No.</th><th>Kegiatan</th>
            @for ($month=1; $month<=6; $month++)<th>{{ $month }}</th>@endfor
            <th>Jumlah Anggota</th><th>Ket.</th>
        </tr></thead>
        <tbody>
            @foreach ($snapshot['entries'] as $row)
                <tr><td>{{ $loop->iteration }}.</td><td>{{ $row['description'] ?? '–' }}</td>
                    @for ($month=0; $month<6; $month++)<td>{{ ($row['months'][$month] ?? false) ? 'X' : '–' }}</td>@endfor
                    <td>{{ $row['participants'] ?? '–' }}</td><td>{{ $row['notes'] ?? '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    @include('administration.partials.weekly-program-body', [
        'weeklyRows' => $snapshot['entries'] ?? [],
        'weeklySignatures' => $snapshot['weekly_signatures'] ?? [],
        'weeklyDate' => $snapshot['scheduled_for'] ?? null,
    ])
@endif
@if ($snapshot['notes'])
    <h2 style="font-size:10pt;">Catatan</h2>
    <p style="font-size:9pt;white-space:pre-line;">{{ $snapshot['notes'] }}</p>
@endif
<p style="font-size:8pt;margin-top:22px;">Dokumen ini memuat snapshot program yang diterbitkan oleh SIMPRAM. Perubahan selanjutnya pada program atau agenda tidak mengubah arsip versi ini. QR verifikasi, nomor revisi, dan metadata penerbitan disertakan melalui sistem penerbitan dokumen.</p>
