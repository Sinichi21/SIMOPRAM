<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Verifikasi Laporan SIMPRAM
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f4f5;
            color: #18181b;
            font-family:
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 16px;
        }

        .card {
            width: 100%;
            max-width: 760px;
            overflow: hidden;
            border: 1px solid #e4e4e7;
            border-radius: 20px;
            background: #ffffff;
            box-shadow:
                0 20px 45px
                rgba(24, 24, 27, 0.08);
        }

        .header {
            padding: 28px;
            border-bottom: 1px solid #e4e4e7;
        }

        .eyebrow {
            margin-bottom: 8px;
            color: #71717a;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: 26px;
            line-height: 1.2;
        }

        .subtitle {
            margin: 10px 0 0;
            color: #71717a;
            line-height: 1.6;
        }

        .content {
            padding: 28px;
        }

        .status {
            margin-bottom: 24px;
            padding: 16px 18px;
            border-radius: 14px;
            border: 1px solid;
        }

        .status strong {
            display: block;
            margin-bottom: 4px;
            font-size: 16px;
        }

        .status-valid {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .status-superseded {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .status-revoked {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                minmax(0, 1fr);
            gap: 1px;
            overflow: hidden;
            border: 1px solid #e4e4e7;
            border-radius: 14px;
            background: #e4e4e7;
        }

        .item {
            min-width: 0;
            padding: 16px;
            background: #ffffff;
        }

        .label {
            margin-bottom: 6px;
            color: #71717a;
            font-size: 12px;
        }

        .value {
            font-weight: 650;
            line-height: 1.45;
            word-break: break-word;
        }

        .checksum-wrap {
            margin-top: 18px;
            padding: 16px;
            border-radius: 14px;
            background: #f4f4f5;
        }

        .checksum {
            margin-top: 6px;
            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;
            font-size: 12px;
            line-height: 1.65;
            word-break: break-all;
        }

        .privacy {
            margin-top: 20px;
            color: #71717a;
            font-size: 13px;
            line-height: 1.6;
        }

        .footer {
            padding: 18px 28px;
            border-top: 1px solid #e4e4e7;
            color: #71717a;
            font-size: 12px;
        }

        @media (max-width: 640px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .header,
            .content {
                padding: 22px;
            }
        }
    </style>
</head>

<body>
@php
    $closure =
        $verification->closure;

    $school =
        $verification->school;
@endphp

<div class="page">
    <main class="card">
        <header class="header">
            <div class="eyebrow">
                SIMPRAM
            </div>

            <h1>
                Verifikasi Laporan
            </h1>

            <p class="subtitle">
                Halaman ini memverifikasi penerbitan
                laporan resmi dan snapshot semester.
                Data pribadi siswa tidak ditampilkan.
            </p>
        </header>

        <section class="content">
            @if ($status === 'valid')
                <div class="status status-valid">
                    <strong>
                        Dokumen Valid
                    </strong>

                    Kode verifikasi terdaftar dan
                    snapshot semester masih berstatus
                    resmi/terkunci.
                </div>
            @elseif ($status === 'superseded')
                <div class="status status-superseded">
                    <strong>
                        Dokumen Versi Lama
                    </strong>

                    Dokumen ini pernah diterbitkan secara
                    resmi, tetapi semester kemudian dibuka
                    kembali untuk koreksi. Periksa versi
                    snapshot yang lebih baru.
                </div>
            @else
                <div class="status status-revoked">
                    <strong>
                        Verifikasi Dicabut
                    </strong>

                    Dokumen ini telah dicabut dari daftar
                    dokumen resmi SIMPRAM.

                    @if ($verification->revocation_reason)
                        Alasan:
                        {{ $verification->revocation_reason }}
                    @endif
                </div>
            @endif

            <div class="grid">
                <div class="item">
                    <div class="label">
                        Sekolah
                    </div>

                    <div class="value">
                        {{ $school?->name ?? '-' }}
                    </div>
                </div>

                <div class="item">
                    <div class="label">
                        Jenis Dokumen
                    </div>

                    <div class="value">
                        Rekap Nilai Ekstrakurikuler Pramuka
                    </div>
                </div>

                <div class="item">
                    <div class="label">
                        Tahun Ajaran
                    </div>

                    <div class="value">
                        {{ $closure?->academicYear?->name ?? '-' }}
                    </div>
                </div>

                <div class="item">
                    <div class="label">
                        Semester
                    </div>

                    <div class="value">
                        {{ $closure?->semester?->name ?? '-' }}
                    </div>
                </div>

                <div class="item">
                    <div class="label">
                        Versi Snapshot
                    </div>

                    <div class="value">
                        v{{ $closure?->version ?? '-' }}
                    </div>
                </div>

                <div class="item">
                    <div class="label">
                        Snapshot Dikunci
                    </div>

                    <div class="value">
                        {{ $closure?->locked_at?->format('d/m/Y H:i:s') ?? '-' }}
                    </div>
                </div>

                <div class="item">
                    <div class="label">
                        Dokumen Diterbitkan
                    </div>

                    <div class="value">
                        {{ $verification->issued_at?->format('d/m/Y H:i:s') ?? '-' }}
                    </div>
                </div>

                <div class="item">
                    <div class="label">
                        Kode Verifikasi
                    </div>

                    <div class="value">
                        {{ $verification->code }}
                    </div>
                </div>
            </div>

            <div class="checksum-wrap">
                <div class="label">
                    Snapshot Checksum SHA-256
                </div>

                <div class="checksum">
                    {{ $verification->snapshot_checksum ?? '-' }}
                </div>
            </div>

            <p class="privacy">
                Checksum di atas adalah identitas
                integritas snapshot nilai semester pada
                saat laporan diterbitkan. Halaman ini
                tidak menampilkan nama siswa, NIS,
                nilai individu, atau data pribadi peserta
                didik.
            </p>
        </section>

        <footer class="footer">
            SIMPRAM · Sistem Informasi Pramuka
        </footer>
    </main>
</div>
</body>
</html>
