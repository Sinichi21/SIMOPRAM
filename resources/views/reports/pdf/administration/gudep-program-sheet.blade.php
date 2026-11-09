@include('administration.partials.gudep-document-letterhead', ['letterhead' => $snapshot['letterhead']])
@if ($snapshot['kind'] === 'semester')
    <div style="font-family:'Times New Roman',Times,serif;">
        <h1 style="text-align:center;font-size:14pt;margin:13px 0 4px;">PROGRAM KERJA SEMESTER</h1>
        <p style="text-align:center;font-size:10pt;margin-top:0;">
            GUGUSDEPAN {{ $snapshot['gudep_program_number'] }} — {{ $snapshot['year_name'] }}
        </p>
        <table style="width:100%;font-size:9pt;margin:10px 0 15px;border-collapse:collapse;">
            <tr><td style="width:25%;">Judul program</td><td>: {{ $snapshot['title'] }}</td></tr>
            <tr><td>Gugusdepan</td><td>: {{ $snapshot['gudep_program_number'] }}</td></tr>
            <tr><td>Tahun ajaran</td><td>: {{ $snapshot['year_name'] }}</td></tr>
            <tr><td>Semester</td><td>: {{ $snapshot['semester_name'] ?: '–' }}</td></tr>
            <tr><td>Golongan</td><td>: {{ $snapshot['level_name'] ?: '–' }}</td></tr>
        </table>
        <h2 style="font-size:10pt;">Rencana Kegiatan Semester</h2>
        <table style="width:100%;table-layout:fixed;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="4">
            <thead><tr style="background-color:#eee;">
                <th style="width:5%;">No.</th><th style="width:34%;">Kegiatan</th>
                @foreach ($snapshot['semester_month_labels'] ?? ['Agu','Sep','Okt','Nov','Des'] as $month)<th style="width:5%;">{{ $month }}</th>@endforeach
                <th style="width:14%;">Jumlah Anggota</th><th style="width:17%;">Ket.</th>
            </tr></thead>
            <tbody>
                @foreach ($snapshot['entries'] as $row)
                    <tr style="page-break-inside:avoid;">
                        <td>{{ $loop->iteration }}.</td><td>{{ $row['description'] ?? '–' }}</td>
                        @foreach ($snapshot['semester_month_labels'] ?? ['Agu','Sep','Okt','Nov','Des'] as $monthIndex => $month)<td style="text-align:center;">{{ ($row['months'][$monthIndex] ?? false) ? 'X' : '–' }}</td>@endforeach
                        <td style="text-align:center;">{{ $row['participants'] ?? '–' }}</td>
                        <td>{{ $row['notes'] ?? '–' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif ($snapshot['kind'] === 'annual')
    @include(($snapshot['document_layout'] ?? 'narrative') === 'monthly_matrix'
        ? 'administration.partials.annual-program-matrix'
        : 'administration.partials.annual-program-body', [
        'entries' => $snapshot['entries'],
        'kwarcab' => $snapshot['gudep_kwarcab'] ?? '',
        'maleNumber' => $snapshot['gudep_male_number'] ?? '',
        'femaleNumber' => $snapshot['gudep_female_number'] ?? '',
        'programNumber' => $snapshot['gudep_program_number'] ?? '–',
        'yearName' => $snapshot['year_name'],
    ])
@else
    @include('administration.partials.weekly-program-body', [
        'weeklyRows' => $snapshot['entries'] ?? [],
        'weeklySignatures' => $snapshot['weekly_signatures'] ?? [],
        'weeklyDate' => $snapshot['scheduled_for'] ?? null,
    ])
@endif

@if (filled($snapshot['notes'] ?? null))
    <div style="font-family:'Times New Roman',Times,serif;page-break-inside:avoid;margin-top:12px;">
        <strong style="font-size:9pt;">Catatan</strong>
        <div style="font-size:9pt;white-space:pre-line;">{{ $snapshot['notes'] }}</div>
    </div>
@endif

@if ($snapshot['kind'] !== 'weekly')
    @include('administration.partials.program-signatures', [
        'programSignatures' => $snapshot['signatures'] ?? [],
        'programDate' => $snapshot['program_date'] ?? '',
        'programCity' => $snapshot['signing_city'] ?? '',
        'programNumber' => $snapshot['gudep_program_number'] ?? '',
    ])
@endif
{{-- The tembusan and archival disclaimer are drawn at the bottom of the LAST PDF
     page by the official publisher, directly above the shared verification QR. --}}
