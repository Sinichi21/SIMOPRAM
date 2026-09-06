<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>LPJ Pramuka</title>
    <style>
        @page { 
            size: A4 portrait;
            margin: 12mm 14mm 12mm 14mm; 
        }
        * { box-sizing: border-box; }
        body {
            color: #000;
            font-family: "DejaVu Serif", serif;
            font-size: 9px;
            line-height: 1.25;
            margin: 0;
        }
        h1, h2, h3, p { margin-top: 0; }
        .page-break { page-break-after: always; }
        .avoid-break { page-break-inside: avoid; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .small { font-size: 7.5px; }
        .muted { color: #555; }

        .letterhead {
            border-bottom: 1.6px solid #000;
            margin-bottom: 6px;
            min-height: 22mm;
            padding: 0 2mm 3mm;
            position: relative;
            text-align: center;
        }
        .letterhead-logo-left,
        .letterhead-logo-right {
            height: 17mm;
            max-width: 20mm;
            object-fit: contain;
            position: absolute;
            top: 0;
        }
        .letterhead-logo-left { left: 1mm; }
        .letterhead-logo-right { right: 1mm; }
        .letterhead-title {
            font-size: 10px;
            font-weight: bold;
            line-height: 1.15;
            margin: 0 23mm;
        }
        .letterhead-address {
            font-size: 7.5px;
            margin: 1mm 23mm 0;
        }

        .cover {
            height: 273mm;
            margin: -12mm -14mm;
            padding: 32mm 25mm 24mm;
            position: relative;
            text-align: center;
        }
        .cover-border {
            height: 297mm;
            left: 0;
            position: absolute;
            top: 0;
            width: 210mm;
            z-index: -1;
        }
        .cover-logos { height: 30mm; margin-top: 8mm; }
        .cover-logos img { height: 24mm; margin: 0 3mm; max-width: 30mm; object-fit: contain; }
        .cover-title { font-size: 14px; font-weight: bold; line-height: 1.25; margin-top: 8mm; }
        .cover-author { font-size: 11px; font-weight: bold; margin-top: 65mm; }
        .cover-school { font-size: 12px; font-weight: bold; line-height: 1.35; margin-top: 42mm; }

        .document-title {
            font-size: 11px;
            font-weight: bold;
            line-height: 1.2;
            margin: 3mm 0 4mm;
            text-align: center;
        }
        .month-label { font-size: 10px; font-weight: bold; margin-top: 2mm; text-align: center; }

        table { border-collapse: collapse; width: 100%; }
        .data-table td { padding: 1.2mm 1mm; vertical-align: top; }
        .data-table td:first-child { width: 8mm; }
        .data-table td:nth-child(2) { width: 49mm; }

        .report-table th,
        .report-table td {
            border: 0.7px solid #333;
            padding: 1.2mm 1.3mm;
            vertical-align: top;
        }
        .report-table th {
            background: #dcebcf;
            font-weight: bold;
            text-align: center;
        }
        .report-table .number { text-align: center; width: 8mm; }
        .report-table .date { width: 38mm; }
        .report-table ul { margin: 0; padding-left: 4mm; }
        .report-table li { margin: 0 0 .5mm; }

        .attendance-table { font-size: 7.2px; table-layout: auto; }
        .attendance-table th,
        .attendance-table td {
            border: 0.65px solid #333;
            padding: .7mm .6mm;
            text-align: center;
            vertical-align: middle;
        }
        .attendance-table th { background: #dcebcf; }
        .attendance-table .col-no { width: 5mm; }
        .attendance-table .col-name { text-align: left; width: 65mm; }
        .attendance-table .col-class { width: 1%; white-space: n0owrap; }
        .attendance-table th.date-column,
        .attendance-table td.date-column { white-space: nowrap; }
        .attendance-table .holiday { background: #a7a7a7; font-size: 6.5px; font-weight: bold; line-height: 1.1; }
        .attendance-table .status { font-weight: bold; }
        .attendance-note { font-size: 6.8px; margin-top: 1.5mm; }

        .signature-table { margin-top: 9mm; }
        .signature-table td { text-align: center; vertical-align: top; width: 50%; }
        .signature-space { height: 18mm; }
        .signature-name { font-weight: bold; text-decoration: underline; }

        .approval-title { margin-top: 4mm; text-align: center; }
        .approval-title h1 { font-size: 14px; margin-bottom: 2mm; }
        .approval-meta { margin: 8mm auto 0; width: 90%; }
        .approval-meta td { padding: 1.2mm 1mm; vertical-align: top; }
        .approval-meta td:first-child { width: 58mm; }
        .approval-signatures { margin-top: 15mm; }
        .approval-signatures td { text-align: center; width: 50%; vertical-align: top; }
        .approval-principal { margin: 20mm auto 0; text-align: center; width: 55%; }

        .documentation-item { margin-bottom: 7mm; page-break-inside: avoid; }
        .documentation-date { font-size: 9px; font-weight: bold; margin-bottom: 2mm; }
        .documentation-grid { width: 100%; }
        .documentation-grid td { padding: 1.5mm; text-align: center; vertical-align: top; width: 50%; }
        .documentation-grid img { height: 52mm; max-width: 78mm; object-fit: contain; }
        .documentation-caption { font-size: 6.5px; margin-top: 1mm; }

        .empty-box {
            border: 0.7px dashed #777;
            color: #555;
            margin-top: 5mm;
            padding: 10mm;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $responsibleCoach = $documentSetting?->responsibleCoach;
    $principalName = $documentSetting?->principal_name ?: $scoutGroup?->kamabigus_name ?: '........................';
    $principalNip = $documentSetting?->principal_nip ?: '........................';
    $coachName = $responsibleCoach?->name ?: $scoutGroup?->head_coach_name ?: '........................';
    $coachIdentifier = $responsibleCoach?->nip
        ? 'NTA. '.$responsibleCoach->nip
        : '';
    $signingCity = $documentSetting?->signing_city ?: ($school->city ?: '................');
@endphp

@if ($periodType === 'semester')
    @include('reports.pdf.lpj.partials.cover')
    @include('reports.pdf.lpj.partials.activity-data')
    @include('reports.pdf.lpj.partials.approval')
@endif

@forelse ($reportMonths as $reportMonth)
    @include('reports.pdf.lpj.partials.monthly-activities', ['reportMonth' => $reportMonth])
    @include('reports.pdf.lpj.partials.coach-attendance', ['reportMonth' => $reportMonth])

    @foreach ($reportMonth['attendanceClasses'] as $classData)
        @foreach ($classData['students']->chunk(24) as $studentChunk)
            @include('reports.pdf.lpj.partials.student-attendance', [
                'reportMonth' => $reportMonth,
                'classData' => $classData,
                'studentChunk' => $studentChunk,
                'isLastChunk' => $loop->last,
                'chunkIndex' => $loop->index,
            ])
        @endforeach
    @endforeach

    @if ($reportMonth['documentation']->isNotEmpty())
        @include('reports.pdf.lpj.partials.documentation', ['reportMonth' => $reportMonth])
    @endif
@empty
    @include('reports.pdf.lpj.partials.letterhead')
    <div class="empty-box">Belum ada kegiatan pada periode ini.</div>
@endforelse
</body>
</html>
