@if (
    isset($verification)
    && $verification
    && isset($verificationQrDataUri)
    && $verificationQrDataUri
)
    <style>
        .simpram-verification-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -19mm;
            height: 16mm;
            border-top: .55px solid #8a8a8a;
            padding-top: 1.5mm;
            color: #3f3f46;
            font-family: DejaVu Sans, sans-serif;
            font-size: 6.5pt;
            line-height: 1.12;
        }

        .simpram-verification-footer table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .simpram-verification-footer td {
            border: 0 !important;
            padding: 0 !important;
            vertical-align: middle;
        }

        .simpram-verification-footer .verification-footer-qr {
            width: 17mm;
        }

        .simpram-verification-footer .verification-footer-qr img {
            display: block;
            width: 14mm;
            height: 14mm;
        }

        .simpram-verification-footer .verification-footer-title {
            font-weight: 700;
            font-size: 7pt;
        }

        .simpram-verification-footer .verification-footer-code {
            margin-top: .6mm;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 5.6pt;
            word-break: break-all;
        }

        .simpram-verification-footer .verification-footer-note {
            margin-top: .5mm;
            font-size: 5.7pt;
        }
    </style>

    <div class="simpram-verification-footer">
        <table>
            <tr>
                <td class="verification-footer-qr">
                    <img src="{{ $verificationQrDataUri }}" alt="QR Verifikasi SIMPRAM">
                </td>
                <td>
                    <div class="verification-footer-title">Verifikasi Dokumen SIMPRAM</div>
                    <div class="verification-footer-note">
                        QR dan kode ini sama pada setiap halaman berkas resmi.
                        Pindai untuk memeriksa status dan integritas dokumen.
                    </div>
                    <div class="verification-footer-code">
                        Kode: {{ $verification->code }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endif
