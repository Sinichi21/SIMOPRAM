<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $verification->publicTitle() }}</title>
    <style>
        @page { margin: 20mm 17mm {{ ($reserveLastPageFooter ?? false) ? '65mm' : '26mm' }} 17mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #18181b; }
        @if (($reserveLastPageFooter ?? false))
            /* Keep the existing QR at the physical foot of A4 while reserving
               the last-page tembusan/disclaimer area above it. */
            .simpram-verification-footer { bottom: -58mm !important; }
        @endif
    </style>
</head>
<body>
    @include('reports.pdf.partials.verification-footer')
    @if ($gudepLetterhead ?? null)
        @include('administration.partials.gudep-document-letterhead', ['letterhead' => $gudepLetterhead])
    @endif
    @include($contentView, $contentData)
</body>
</html>
