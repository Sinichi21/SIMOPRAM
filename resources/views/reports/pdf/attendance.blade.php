<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>
        Rekap Absensi Pramuka
    </title>

    <style>
        @page {
            margin: 12mm 16mm 27mm 16mm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 9pt;
            color: #111;
        }

        /* Kop resmi: identik dengan kop PDF Persuratan. */
        .letterhead {
            border-bottom: 1.6px solid #000;
            margin-bottom: 4mm;
            min-height: 22mm;
            padding: 0 2mm 2.5mm;
            position: relative;
            text-align: center;
        }

        .letterhead-logo-left,
        .letterhead-logo-right {
            height: 21mm;
            max-width: 23mm;
            object-fit: contain;
            position: absolute;
            top: 0;
        }

        .letterhead-logo-left { left: 1mm; }
        .letterhead-logo-right { right: 1mm; }

        .letterhead-title {
            font-family: "Times New Roman", Times, serif;
            font-size: 14pt !important;
            font-weight: bold;
            line-height: 1.05;
            margin: 0 23mm;
        }

        .letterhead-address {
            font-family: "Times New Roman", Times, serif;
            font-size: 10pt !important;
            line-height: 1.1;
            margin: .8mm 23mm 0;
        }

        .report-title {
            margin: 0 0 4mm;
            text-align: center;
        }

        .report-title h1 {
            font-size: 13pt;
            margin: 0;
            text-transform: uppercase;
        }

        .meta {
            width: 100%;
            margin-bottom: 14px;
        }

        .meta td {
            padding: 2px 4px;
        }

        .meta-label {
            width: 110px;
        }

        .report {
            width: 100%;
            border-collapse: collapse;
        }

        .report th,
        .report td {
            border: 1px solid #222;
            padding: 4px;
        }

        .report th {
            background: #ededed;
            font-weight: bold;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .signature {
            width: 100%;
            margin-top: 28px;
        }

        .signature td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }

        .signature-space {
            height: 55px;
        }

        .footer {
            margin-top: 10px;
            font-size: 8px;
            color: #555;
        }
    </style>
</head>

<body>
@include('reports.pdf.partials.verification-footer')

    @include('reports.pdf.lpj.partials.letterhead')

    <div class="report-title">
        <h1>Rekap Absensi Ekstrakurikuler Pramuka</h1>
    </div>


    <table class="meta">

        <tr>
            <td class="meta-label">
                Tahun Ajaran
            </td>

            <td>
                :
                {{ $academicYear?->name ?? '-' }}
            </td>

            <td class="meta-label">
                Kelas
            </td>

            <td>
                :
                {{ $classroom?->name ?? 'Semua Kelas' }}
            </td>
        </tr>

        <tr>
            <td>
                Semester
            </td>

            <td>
                :
                {{ $semester?->name ?? 'Semua Semester' }}
            </td>

            <td>
                Total Sesi
            </td>

            <td>
                :
                {{ $sessionCount }}
            </td>
        </tr>

    </table>


    <table class="report">

        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Pertemuan</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpa</th>
                <th>Belum Dicatat</th>
                <th>% Hadir</th>
            </tr>
        </thead>

        <tbody>

            @forelse ($rows as $index => $row)

                @php
                    $student =
                        $row['student'];

                    $enrollment =
                        $academicYear
                            ? $student
                                ->enrollments
                                ->firstWhere(
                                    'academic_year_id',
                                    $academicYear->id
                                )
                            : null;
                @endphp

                <tr>
                    <td class="center">
                        {{ $index + 1 }}
                    </td>

                    <td class="center">
                        {{ $student->nis }}
                    </td>

                    <td>
                        {{ $student->name }}
                    </td>

                    <td class="center">
                        {{ $enrollment?->classroom?->name ?? '-' }}
                    </td>

                    <td class="center">
                        {{ $row['participants'] }}
                    </td>

                    <td class="center">
                        {{ $row['present'] }}
                    </td>

                    <td class="center">
                        {{ $row['late'] }}
                    </td>

                    <td class="center">
                        {{ $row['sick'] }}
                    </td>

                    <td class="center">
                        {{ $row['excused'] }}
                    </td>

                    <td class="center">
                        {{ $row['absent'] }}
                    </td>

                    <td class="center">
                        {{ $row['unrecorded'] }}
                    </td>

                    <td class="center">
                        {{ number_format(
                            $row[
                                'presence_percentage'
                            ],
                            2
                        ) }}%
                    </td>
                </tr>

            @empty

                <tr>
                    <td
                        colspan="12"
                        class="center"
                    >
                        Belum ada data absensi.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>


    @include(
        'reports.pdf.partials.signature',
        [
            'school' => $school,
            'documentSetting' => $documentSetting,
        ]
    )

    @if ($documentSetting?->document_note)

        <div
            style="
                margin-top: 12px;
                font-size: 8px;
            "
        >
            <strong>Catatan:</strong>
            {{ $documentSetting->document_note }}
        </div>

    @endif

    <div class="footer">
        Dicetak dari SIMPRAM pada
        {{ now()->format('d-m-Y H:i') }}.
    </div>

</body>
</html>
