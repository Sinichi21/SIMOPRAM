<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>
        Rekap Nilai Pramuka
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
            vertical-align: top;
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
            vertical-align: middle;
        }

        .report th {
            background: #ededed;
            text-align: center;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .left {
            text-align: left;
        }

        .nowrap {
            white-space: nowrap;
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
            REKAP NILAI EKSTRAKURIKULER PRAMUKA
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
                Konfigurasi
            </td>

            <td>
                :
                {{ $selectedConfig->name }}
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

                @foreach (
                    $selectedConfig->items
                        ->sortBy('sort_order')
                    as $item
                )
                    <th>
                        {{ $item->factor->name }}

                        <br>

                        ({{ number_format(
                            (float) $item->weight,
                            0
                        ) }}%)
                    </th>
                @endforeach

                <th>
                    Nilai Akhir
                </th>

                <th>
                    Predikat
                </th>

                <th>
                    Deskripsi
                </th>
            </tr>
        </thead>

        <tbody>

            @forelse (
                $students
                as $index => $student
            )

                @php
                    $studentScores =
                        $scores->get(
                            $student->id,
                            collect()
                        );

                    $finalGrade =
                        $finalGrades->get(
                            $student->id
                        );

                    $enrollment =
                        $student
                            ->enrollments
                            ->firstWhere(
                                'academic_year_id',
                                $selectedConfig
                                    ->academic_year_id
                            );
                @endphp

                <tr>
                    <td class="center">
                        {{ $index + 1 }}
                    </td>

                    <td class="center nowrap">
                        {{ $student->nis }}
                    </td>

                    <td>
                        {{ $student->name }}
                    </td>

                    <td class="center">
                        {{ $enrollment?->classroom?->name ?? '-' }}
                    </td>


                    @foreach (
                        $selectedConfig->items
                            ->sortBy('sort_order')
                        as $item
                    )

                        @php
                            $score =
                                $studentScores
                                    ->firstWhere(
                                        'assessment_factor_id',
                                        $item
                                            ->assessment_factor_id
                                    );
                        @endphp

                        <td class="center">
                            {{ $score
                                ? number_format(
                                    (float) $score->score,
                                    2
                                )
                                : '-'
                            }}
                        </td>

                    @endforeach


                    <td class="center">
                        {{ $finalGrade
                            ? number_format(
                                (float) $finalGrade
                                    ->final_score,
                                2
                            )
                            : '-'
                        }}
                    </td>

                    <td class="center">
                        {{ $finalGrade
                            ?->letter_grade
                            ?? '-'
                        }}
                    </td>

                    <td>
                        {{ $finalGrade
                            ?->description
                            ?? '-'
                        }}
                    </td>
                </tr>

            @empty

                <tr>
                    <td
                        colspan="{{ 7 + $selectedConfig->items->count() }}"
                        class="center"
                    >
                        Belum ada data nilai.
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
        Dicetak dari SIMOPRAM pada
        {{ now()->format('d-m-Y H:i') }}.
    </div>

</body>
</html>