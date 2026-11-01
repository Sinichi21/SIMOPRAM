<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Label Aset — {{ $asset->asset_code }}</title>
    <style>
        @page { size: {{ $format === 'a4' ? 'A4' : ($format === 'thermal58' ? '58mm 35mm' : '80mm 40mm') }}; margin: {{ $format === 'a4' ? '10mm' : '2mm' }}; }
        body { font-family: Arial, sans-serif; color: #111; margin: 0; }
        .sheet { display: flex; flex-wrap: wrap; gap: 3mm; }
        .label { box-sizing: border-box; border: 1px solid #555; padding: 2mm; width: {{ $format === 'a4' ? '60mm' : '100%' }}; min-height: {{ $format === 'thermal58' ? '30mm' : '35mm' }}; overflow-wrap: anywhere; break-inside: avoid; }
        .name { font-size: 11px; font-weight: bold; }
        .code { font-size: 10px; font-family: monospace; margin-top: 1mm; }
        .qr { float: right; width: 18mm; height: 18mm; margin-left: 2mm; }
        .detail { font-size: 9px; margin-top: 2mm; }
        @media print { .toolbar { display: none; } .label { border-color: #111; } }
        @media screen { .toolbar { padding: 12px; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Cetak label aset</button></div>
    <div class="sheet">
        @for ($i = 0; $i < $copies; $i++)
            <div class="label">
                <img class="qr" src="{{ $qrDataUri }}" alt="QR aset {{ $asset->asset_code }}">
                <div class="name">{{ $asset->item?->name ?? 'Aset inventaris' }}</div>
                <div class="code">{{ $asset->asset_code }}</div>
                <div class="detail">{{ $asset->serial_number ?: 'Tanpa nomor seri' }}</div>
                <div class="detail">{{ $asset->owner?->display_name ?? 'Pemilik tidak tersedia' }}</div>
            </div>
        @endfor
    </div>
</body>
</html>
