<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $letter->subject }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 16mm 27mm; }
        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #000;
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.35;
        }

        /* Gunakan pt, bukan px. 12px sebelumnya hanya sekitar 9pt di PDF. */
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

        .document-body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.35;
        }
        .document-body p,
        .document-body div { margin: 0 0 2.6mm; }
        .document-body p:last-child,
        .document-body div:last-child { margin-bottom: 0; }
        .document-body ul,
        .document-body ol { margin: 0 0 2.6mm 7mm; padding-left: 5mm; }
        .document-body li { margin: 0 0 1mm; }
        .document-body blockquote {
            border-left: 1.5px solid #777;
            margin: 2mm 0 2.6mm 6mm;
            padding-left: 4mm;
        }
        .document-body table {
            border-collapse: collapse;
            margin: 1.5mm 0 3mm;
            page-break-inside: avoid;
            width: 100%;
        }
        .document-body td,
        .document-body th {
            padding: 1.3mm 1.8mm;
            vertical-align: top;
        }

        /*
         * Tabel baru menyimpan border secara inline agar admin dapat memilih
         * garis / tanpa garis / putus-putus. Untuk template lama yang belum
         * punya style border, pertahankan border default.
         */
        .document-body td:not([style]),
        .document-body th:not([style]) {
            border: .6px solid #555;
        }
        .document-body th { font-weight: bold; text-align: center; }
        .document-body hr { border: 0; border-top: .7px solid #777; margin: 3mm 0; }

        .legacy-meta {
            border-collapse: collapse;
            margin: 0 0 3mm;
            width: 100%;
        }
        .legacy-meta td { border: 0; padding: .25mm 0; vertical-align: top; }
        .legacy-meta .label { width: 22mm; }
        .legacy-meta .sep { text-align: center; width: 4mm; }
        .legacy-recipient { margin: 2mm 0 3mm; line-height: 1.25; }
        .legacy-signature {
            margin: 6mm 0 0 auto;
            page-break-inside: avoid;
            text-align: center;
            width: 48%;
        }
        .legacy-signature-space { height: 14mm; }
        .legacy-signature-name { font-weight: bold; text-decoration: underline; }

        /* Footer QR fixed di setiap halaman. */
        .verification {
            border-top: .6px solid #888;
            bottom: -21mm;
            color: #333;
            font-size: 8pt;
            height: 17mm;
            left: 0;
            padding-top: 2mm;
            position: fixed;
            right: 0;
        }
        .verification table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .verification td { border: 0; padding: 0; vertical-align: middle; }
        .qr { width: 22mm; }
        .qr img { height: 18mm; width: 18mm; }
        .verification-code,
        .verification-note { font-size: 7.5pt; line-height: 1.15; }
    </style>
</head>
<body>
    @include('reports.pdf.lpj.partials.letterhead')

    @if ($flexibleLayout)
        {{-- Template baru mengontrol sendiri Nomor, Lampiran, Perihal, Tujuan, isi, dan blok tanda tangan. --}}
        <div class="document-body">{!! $pdfBody !!}</div>
    @else
        {{-- Compatibility mode untuk template lama yang dibuat sebelum v2.7. --}}
        <table class="legacy-meta">
            <tr><td class="label">Nomor</td><td class="sep">:</td><td>{{ $letter->letter_number }}</td></tr>
            <tr>
                <td class="label">Lampiran</td><td class="sep">:</td>
                <td>{{ $attachmentCount > 0 ? $attachmentCount.' berkas' : '-' }}</td>
            </tr>
            <tr><td class="label">Perihal</td><td class="sep">:</td><td><strong>{{ $letter->subject }}</strong></td></tr>
        </table>

        @if (filled($letter->recipient))
            <div class="legacy-recipient">
                Yth. {{ $letter->recipient }}<br>
                di {{ $recipientLocation }}
            </div>
        @endif

        <div class="document-body">{!! $pdfBody !!}</div>

        <div class="legacy-signature">
            {{ $letter->signatory_position ?: 'Pembina Gugusdepan' }},
            <div class="legacy-signature-space"></div>
            <div class="legacy-signature-name">{{ $letter->signatory_name ?: '........................' }}</div>
            @if ($letter->signatory_identity)
                <div>{{ $letter->signatory_identity }}</div>
            @endif
        </div>
    @endif

    <div class="verification">
        <table>
            <tr>
                <td class="qr">
                    @if ($qrDataUri)
                        <img src="{{ $qrDataUri }}" alt="QR Verifikasi">
                    @endif
                </td>
                <td>
                    <strong>Verifikasi Dokumen SIMPRAM</strong><br>
                    <span class="verification-note">Scan QR untuk memeriksa keaslian dokumen. Kode verifikasi sama pada setiap halaman.</span><br>
                    <span class="verification-code">Kode: {{ $verification->code }}</span>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
