<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>
        Rekap Nilai Ekstrakurikuler Pramuka
    </title>

    <style>
        @page {
            margin: 18mm 12mm 16mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.35;
            color: #111827;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .header h1 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .header h2 {
            margin: 3px 0 0;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .header p {
            margin: 3px 0 0;
            font-size: 9px;
        }

        .divider {
            margin: 9px 0 10px;
            border-top: 2px solid #111827;
            border-bottom: 1px solid #111827;
            height: 3px;
        }

        .meta-table,
        .grade-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table {
            margin-bottom: 10px;
        }

        .meta-table td {
            padding: 2px 3px;
            vertical-align: top;
        }

        .meta-label {
            width: 115px;
            font-weight: 700;
        }

        .meta-separator {
            width: 10px;
        }

        .snapshot-box {
            margin: 0 0 10px;
            padding: 7px 8px;
            border: 1px solid #9ca3af;
            background: #f9fafb;
        }

        .snapshot-title {
            margin-bottom: 4px;
            font-weight: 700;
            font-size: 9px;
        }

        .checksum {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 7px;
            word-break: break-all;
        }

        .grade-table {
            table-layout: fixed;
        }

        .grade-table th,
        .grade-table td {
            border: 1px solid #374151;
            padding: 4px 3px;
            vertical-align: middle;
        }

        .grade-table th {
            background: #f3f4f6;
            text-align: center;
            font-size: 8px;
            font-weight: 700;
        }

        .grade-table td {
            font-size: 8px;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .nowrap {
            white-space: nowrap;
        }

        .description {
            font-size: 7.5px;
            line-height: 1.25;
        }

        .empty {
            padding: 16px 6px !important;
            text-align: center;
            color: #6b7280;
        }

        .signature-table {
            margin-top: 24px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            padding: 0 28px;
            text-align: center;
            vertical-align: top;
        }

        .signature-space {
            height: 52px;
        }

        .signature-name {
            font-weight: 700;
            text-decoration: underline;
        }

        .footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #d1d5db;
            font-size: 7px;
            color: #4b5563;
        }

        .footer .checksum {
            margin-top: 2px;
        }

        .verification-box {
            width: 100%;
            margin: 10px 0 12px;
            border: 1px solid #9ca3af;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .verification-box td {
            padding: 8px;
            vertical-align: middle;
        }

        .verification-qr {
            width: 92px;
            text-align: center;
            border-right: 1px solid #d1d5db;
        }

        .verification-qr img {
            width: 82px;
            height: 82px;
        }

        .verification-code {
            margin-top: 4px;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 7px;
            word-break: break-all;
        }

        .verification-url {
            margin-top: 4px;
            font-size: 7px;
            word-break: break-all;
            color: #4b5563;
        }
    </style>
</head>

<body>
@php
    $items =
        $selectedConfig?->items
        ?? collect();

    /*
    |--------------------------------------------------------------------------
    | SchoolDocumentSetting dibuat defensif karena penamaan field dapat berbeda
    | antar revisi project. data_get() tidak menimbulkan error bila field belum
    | tersedia.
    |--------------------------------------------------------------------------
    */

    $principalName =
        data_get(
            $documentSetting,
            'principal_name'
        )
        ?? data_get(
            $documentSetting,
            'headmaster_name'
        )
        ?? data_get(
            $school,
            'principal_name'
        )
        ?? 'Kepala Sekolah';

    $principalIdentifier =
        data_get(
            $documentSetting,
            'principal_nip'
        )
        ?? data_get(
            $documentSetting,
            'headmaster_nip'
        )
        ?? data_get(
            $school,
            'principal_nip'
        );

    $responsibleCoach =
        data_get(
            $documentSetting,
            'responsibleCoach'
        );

    $coachName =
        data_get(
            $responsibleCoach,
            'name'
        )
        ?? data_get(
            $documentSetting,
            'responsible_coach_name'
        )
        ?? 'Pembina Pramuka';

    $coachIdentifier =
        data_get(
            $responsibleCoach,
            'nip'
        )
        ?? data_get(
            $responsibleCoach,
            'nta'
        )
        ?? data_get(
            $documentSetting,
            'responsible_coach_nip'
        )
        ?? data_get(
            $documentSetting,
            'responsible_coach_nta'
        );

    $signingCity =
        data_get(
            $documentSetting,
            'signing_city'
        )
        ?? data_get(
            $documentSetting,
            'city'
        )
        ?? data_get(
            $school,
            'city'
        )
        ?? 'Denpasar';

    $generatedAt =
        $reportGeneratedAt
        ?? now();
@endphp

<div class="header">
    <h1>
        Rekap Nilai Ekstrakurikuler Pramuka
    </h1>

    <h2>
        {{ $school?->name ?? 'Sekolah' }}
    </h2>

    <p>
        Tahun Ajaran
        {{ $academicYear?->name ?? '-' }}
        @if ($semester)
            |
            Semester
            {{ $semester->name }}
        @endif
    </p>

    <div class="divider"></div>
</div>

<table class="meta-table">
    <tr>
        <td class="meta-label">
            Tahun Ajaran
        </td>

        <td class="meta-separator">
            :
        </td>

        <td>
            {{ $academicYear?->name ?? '-' }}
        </td>

        <td class="meta-label">
            Sumber Data
        </td>

        <td class="meta-separator">
            :
        </td>

        <td>
            {{ $reportSourceLabel ?? 'Data Berjalan' }}
        </td>
    </tr>

    <tr>
        <td class="meta-label">
            Semester
        </td>

        <td class="meta-separator">
            :
        </td>

        <td>
            {{ $semester?->name ?? '-' }}
        </td>

        <td class="meta-label">
            Dibuat Pada
        </td>

        <td class="meta-separator">
            :
        </td>

        <td>
            {{ $generatedAt?->format('d/m/Y H:i:s') ?? '-' }}
        </td>
    </tr>

    <tr>
        <td class="meta-label">
            Kelas
        </td>

        <td class="meta-separator">
            :
        </td>

        <td>
            {{ $classroom?->name ?? 'Semua Kelas' }}
        </td>

        <td class="meta-label">
            Jumlah Siswa
        </td>

        <td class="meta-separator">
            :
        </td>

        <td>
            {{ number_format($students->count()) }}
        </td>
    </tr>
</table>

@if (
    ($reportSource ?? null) === 'snapshot'
    &&
    $selectedClosure
)
    <div class="snapshot-box">
        <div class="snapshot-title">
            Snapshot Nilai Resmi
        </div>

        <table class="meta-table" style="margin-bottom: 0;">
            <tr>
                <td class="meta-label">
                    Versi Snapshot
                </td>

                <td class="meta-separator">
                    :
                </td>

                <td>
                    v{{ $selectedClosure->version }}
                </td>

                <td class="meta-label">
                    Dikunci Pada
                </td>

                <td class="meta-separator">
                    :
                </td>

                <td>
                    {{ $selectedClosure->locked_at?->format('d/m/Y H:i:s') ?? '-' }}
                </td>
            </tr>

            <tr>
                <td class="meta-label">
                    Jumlah Snapshot
                </td>

                <td class="meta-separator">
                    :
                </td>

                <td>
                    {{ number_format($selectedClosure->snapshot_count) }}
                </td>

                <td class="meta-label">
                    Versi Bobot Kehadiran
                </td>

                <td class="meta-separator">
                    :
                </td>

                <td>
                    {{ $selectedClosure->attendance_source_version ?? '-' }}
                </td>
            </tr>

            <tr>
                <td class="meta-label">
                    Checksum SHA-256
                </td>

                <td class="meta-separator">
                    :
                </td>

                <td colspan="4" class="checksum">
                    {{ $selectedClosure->snapshot_checksum ?? '-' }}
                </td>
            </tr>
        </table>
    </div>
@endif

@if (
    ($reportSource ?? null) === 'snapshot'
    &&
    $selectedClosure
    &&
    $verification
    &&
    $verificationQrDataUri
)
    <table class="verification-box">
        <tr>
            <td class="verification-qr">
                <img
                    src="{{ $verificationQrDataUri }}"
                    alt="QR Verifikasi Laporan"
                >
            </td>

            <td>
                <div style="font-weight: 700; font-size: 9px;">
                    Verifikasi Dokumen Resmi
                </div>

                <div style="margin-top: 3px; font-size: 8px; line-height: 1.4;">
                    Pindai QR untuk memeriksa sekolah,
                    periode, versi snapshot, status dokumen,
                    dan checksum tanpa menampilkan data pribadi siswa.
                </div>

                <div class="verification-code">
                    Kode:
                    {{ $verification->code }}
                </div>

                <div class="verification-url">
                    {{ $verificationUrl }}
                </div>
            </td>
        </tr>
    </table>
@endif

<table class="grade-table">
    <thead>
        <tr>
            <th style="width: 28px;">
                No.
            </th>

            <th style="width: 68px;">
                NIS
            </th>

            <th style="width: 135px;">
                Nama Siswa
            </th>

            <th style="width: 62px;">
                Kelas
            </th>

            @foreach (
                $items
                as $item
            )
                <th>
                    {{ $item->factor?->name ?? 'Faktor' }}

                    <br>

                    <span style="font-size: 7px; font-weight: 400;">
                        {{ number_format(
                            (float) $item->weight,
                            2
                        ) }}%
                    </span>
                </th>
            @endforeach

            <th style="width: 58px;">
                Nilai Akhir
            </th>

            <th style="width: 48px;">
                Predikat
            </th>

            <th style="width: 180px;">
                Deskripsi
            </th>
        </tr>
    </thead>

    <tbody>
        @forelse (
            $students
            as $student
        )
            @php
                $enrollment =
                    $student
                        ->enrollments
                        ->first();

                $studentScores =
                    $scores->get(
                        $student->id,
                        collect()
                    );

                $final =
                    $finalGrades->get(
                        $student->id
                    );
            @endphp

            <tr>
                <td class="text-center">
                    {{ $loop->iteration }}
                </td>

                <td class="text-center nowrap">
                    {{ $student->nis ?? '-' }}
                </td>

                <td>
                    {{ $student->name ?? '-' }}
                </td>

                <td class="text-center">
                    {{ $enrollment?->classroom?->name ?? '-' }}
                </td>

                @foreach (
                    $items
                    as $item
                )
                    @php
                        $score =
                            $studentScores->get(
                                $item->assessment_factor_id
                            );
                    @endphp

                    <td class="text-center">
                        @if (
                            $score
                            &&
                            $score->score !== null
                        )
                            {{ number_format(
                                (float) $score->score,
                                2,
                                ',',
                                '.'
                            ) }}
                        @else
                            -
                        @endif
                    </td>
                @endforeach

                <td class="text-center">
                    @if (
                        $final
                        &&
                        $final->final_score !== null
                    )
                        {{ number_format(
                            (float) $final->final_score,
                            2,
                            ',',
                            '.'
                        ) }}
                    @else
                        -
                    @endif
                </td>

                <td class="text-center">
                    {{ $final?->letter_grade ?? '-' }}
                </td>

                <td class="description">
                    {{ $final?->description ?? '-' }}
                </td>
            </tr>
        @empty
            <tr>
                <td
                    colspan="{{ 7 + $items->count() }}"
                    class="empty"
                >
                    Tidak ada data siswa pada filter laporan yang dipilih.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="signature-table">
    <tr>
        <td>
            Mengetahui,

            <br>

            Kepala Sekolah

            <div class="signature-space"></div>

            <div class="signature-name">
                {{ $principalName }}
            </div>

            @if ($principalIdentifier)
                <div>
                    NIP. {{ $principalIdentifier }}
                </div>
            @endif
        </td>

        <td>
            {{ $signingCity }},
            {{ $generatedAt?->translatedFormat('d F Y') ?? '-' }}

            <br>

            Pembina Pramuka

            <div class="signature-space"></div>

            <div class="signature-name">
                {{ $coachName }}
            </div>

            @if ($coachIdentifier)
                <div>
                    {{ is_numeric($coachIdentifier) ? 'NIP.' : 'NTA.' }}
                    {{ $coachIdentifier }}
                </div>
            @endif
        </td>
    </tr>
</table>

<div class="footer">
    Dokumen dibuat melalui SIMOPRAM.

    @if (
        ($reportSource ?? null) === 'snapshot'
        &&
        $selectedClosure
    )
        Dokumen ini menggunakan Snapshot Nilai Resmi semester
        versi {{ $selectedClosure->version }}.

        <div class="checksum">
            SHA-256:
            {{ $selectedClosure->snapshot_checksum ?? '-' }}
        </div>
    @endif
</div>

</body>
</html>
