<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $program->title }} — {{ $gudep->name }}</title>
    <style>
        @page { size: A4 {{ $program->kind === 'annual' && $program->document_layout === 'monthly_matrix' ? 'landscape' : 'portrait' }}; margin: 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #171717; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.5; }
        .sheet { max-width: {{ $program->kind === 'annual' && $program->document_layout === 'monthly_matrix' ? '297mm' : '210mm' }}; margin: 0 auto; padding: 18px; }
        h1 { margin: 3px 0; font-size: 17px; text-align: center; text-transform: uppercase; }
        h2 { margin: 20px 0 7px; font-size: 12px; }
        .center { text-align: center; }
        .subtitle { font-size: 12px; }
        .muted { color: #555; }
        .header { border-bottom: 2px solid #222; padding-bottom: 12px; margin-bottom: 15px; }
        .metadata { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .metadata td { border: none; padding: 2px 4px; vertical-align: top; }
        .metadata td:first-child { width: 115px; }
        table.rows { width: 100%; border-collapse: collapse; margin: 8px 0 13px; table-layout: fixed; }
        .rows th, .rows td { border: 1px solid #444; padding: 5px 6px; vertical-align: top; overflow-wrap: anywhere; }
        .rows th { text-align: center; background: #f2f2f2; font-weight: bold; }
        .rows td.number { width: 40px; text-align: center; }
        .rows td.center { text-align: center; }
        .note { margin-top: 15px; white-space: pre-line; }
        .controls { display: flex; gap: 10px; align-items: center; padding: 12px 16px; background: #f4f4f5; }
        .controls a, .controls button { border: 1px solid #888; color: #111; background: #fff; border-radius: 6px; padding: 7px 12px; font: inherit; cursor: pointer; text-decoration: none; }
        .signatures { display: flex; justify-content: space-between; gap: 25px; margin-top: 35px; text-align: center; }
        .signatures > div { width: 48%; }
        .signature-line { margin-top: 54px; border-bottom: 1px solid #222; }
        .draft { font-size: 10px; text-align: center; color: #666; margin-top: 15px; }
        h2, tr { break-inside: avoid; page-break-inside: avoid; }
        @media print { .controls { display: none; } .sheet { padding: 0; } }
    </style>
</head>
<body>
    <div class="controls">
        <a href="{{ route('administration.programs') }}">Kembali ke Program Kerja</a>
        <button type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
        <span class="muted">Pratinjau cetak — belum merupakan dokumen resmi terbit.</span>
    </div>
    <main class="sheet">
        @include('administration.partials.gudep-document-letterhead', ['letterhead' => $letterhead])
        @if ($program->kind === 'semester')
        <header class="header">
            <p class="center" style="margin:0;font-size:12px;font-weight:bold">GERAKAN PRAMUKA</p>
            <h1>{{ ['annual' => 'Program Kerja Tahunan', 'semester' => 'Program Kerja Semester', 'weekly' => 'Program Latihan Mingguan'][$program->kind] ?? 'Program Kerja' }}</h1>
            <p class="center subtitle" style="margin:0">{{ $gudep->name }} — {{ $year->name }}</p>
        </header>

        <table class="metadata">
            <tr><td>Judul</td><td>: {{ $program->title }}</td></tr>
            <tr><td>Gugusdepan</td><td>: {{ $gudep->name }}</td></tr>
            <tr><td>Nomor Gudep</td><td>: {{ $programNumber ?: '–' }}</td></tr>
            <tr><td>Tahun Ajaran</td><td>: {{ $year->name }}</td></tr>
            @if ($program->kind !== 'annual')
                <tr><td>Semester</td><td>: {{ $program->semester?->name ?? '–' }}</td></tr>
                <tr><td>Golongan</td><td>: {{ $program->scoutLevel?->name ?? '–' }}</td></tr>
            @endif
            @if ($program->kind === 'weekly')
                <tr><td>Tanggal Latihan</td><td>: {{ $program->scheduled_for?->format('d/m/Y') ?? '–' }}</td></tr>
                <tr><td>Agenda SIMPRAM</td><td>: {{ $program->activity?->title ?? '–' }}</td></tr>
                @if ($program->activity)
                    <tr><td>Waktu</td><td>: {{ $program->activity->start_at?->format('H:i') ?? '–' }}–{{ $program->activity->end_at?->format('H:i') ?? '–' }}</td></tr>
                    <tr><td>Tempat</td><td>: {{ $program->activity->location ?: '–' }}</td></tr>
                @endif
            @endif
        </table>

        @endif

        @if ($program->kind === 'annual')
            @include($program->document_layout === 'monthly_matrix' ? 'administration.partials.annual-program-matrix' : 'administration.partials.annual-program-body', [
                'entries' => $program->entries ?? [],
                'kwarcab' => $gudep->kwarcab,
                'maleNumber' => $gudep->male_number,
                'femaleNumber' => $gudep->female_number,
                'yearName' => $year->name,
                'programNumber' => $programNumber,
            ])
        @elseif ($program->kind === 'semester')
            <h2>Rencana Kegiatan Semester</h2>
            <table class="rows">
                <thead><tr><th style="width:29px">No.</th><th>Uraian Kegiatan</th>
                    @foreach (\App\Support\GudepProgramSemesterProjection::monthLabels($program->semester) as $month)<th style="width:25px">{{ $month }}</th>@endforeach
                    <th style="width:45px">Anggota</th><th>Ket.</th>
                </tr></thead>
                <tbody>
                    @foreach ($program->entries ?? [] as $entry)
                        <tr>
                            <td class="number">{{ $loop->iteration }}.</td>
                            <td>{{ $entry['description'] ?? '–' }}</td>
                            @foreach (\App\Support\GudepProgramSemesterProjection::monthLabels($program->semester) as $monthIndex => $month)
                                <td class="center">{{ ($entry['months'][$monthIndex] ?? false) ? '✓' : '–' }}</td>
                            @endforeach
                            <td class="center">{{ $entry['participants'] ?? '–' }}</td>
                            <td>{{ $entry['notes'] ?: '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="muted" style="font-size:10px">Ganjil Agustus–Desember; Genap Januari–Juli, mengikuti Program Tahunan.</p>
        @else
            @include('administration.partials.weekly-program-body', [
                'weeklyRows' => $program->entries ?? [],
                'weeklySignatures' => $weeklySignatures ?? [],
                'weeklyDate' => $program->scheduled_for?->format('d/m/Y'),
            ])
        @endif

        @if ($program->notes)
            <h2>Catatan</h2>
            <p class="note">{{ $program->notes }}</p>
        @endif

        @if ($program->kind !== 'weekly')
        @include('administration.partials.program-signatures', [
            'programSignatures' => $programSignatures ?? [],
            'programCity' => $programCity ?? '',
            'programNumber' => $programNumber ?? '',
            'programDate' => $program->updated_at?->format('d/m/Y'),
        ])
        @endif
        <p class="draft">Cetak dari SIMPRAM — pratinjau kerja, belum diterbitkan atau ditandatangani secara digital.</p>
    </main>
</body>
</html>
