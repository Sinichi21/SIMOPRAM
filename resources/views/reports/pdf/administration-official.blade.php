<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $verification->publicTitle() }}</title>
    <style>
        @page { margin: 20mm 17mm 26mm 17mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #18181b; }
    </style>
</head>
<body>
    @include('reports.pdf.partials.verification-footer')
    @include($contentView, $contentData)
</body>
</html>
