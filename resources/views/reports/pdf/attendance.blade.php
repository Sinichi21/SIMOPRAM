<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>
        Rekap Absensi Pramuka
    </title>

    <style>
        @page {
            margin: 18mm 12mm 18mm 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111;
        }

        .header {
            text-align: center;
            margin-bottom: 14px;
        }

        .header h1 {
            font-size: 15px;
            margin: 0 0 4px;
        }

        .header h2 {
            font-size: 12px;
            margin: 0 0 4px;
        }

        .header p {
            margin: 2px 0;
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

    <div class="header">

        <h1>
            REKAP ABSENSI EKSTRAKURIKULER PRAMUKA
        </h1>

        <h2>
            {{ $school->name }}
        </h2>

        @if ($school->npsn)
            <p>
                NPSN: {{ $school->npsn }}
            </p>
        @endif

        @if (
            $documentSetting?->gudep_male_number
            ||
            $documentSetting?->gudep_female_number
        )

            <p>
                Gugus Depan:

                @if (
                    $documentSetting
                        ?->gudep_male_number
                )
                    Putra
                    {{ $documentSetting
                        ->gudep_male_number }}
                @endif


                @if (
                    $documentSetting
                        ?->gudep_male_number
                    &&
                    $documentSetting
                        ?->gudep_female_number
                )
                    |
                @endif


                @if (
                    $documentSetting
                        ?->gudep_female_number
                )
                    Putri
                    {{ $documentSetting
                        ->gudep_female_number }}
                @endif
            </p>

        @endif

        @if ($school->address)
            <p>
                {{ $school->address }}
            </p>
        @endif

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
