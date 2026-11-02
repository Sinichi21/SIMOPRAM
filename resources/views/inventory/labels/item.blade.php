<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Label Inventaris — {{ $item->item_code }}</title>
    <style>
        /*
         * Inventory label layout matches Android DirectLabelPrinter "Opsi A":
         * QR on the LEFT, information on the RIGHT.
         * Keep server-generated verification QR unchanged.
         */
        @page { size: {{ $format === 'a4' ? 'A4' : ($format === 'thermal58' ? '58mm 35mm' : '80mm 40mm') }}; margin: {{ $format === 'a4' ? '10mm' : '2mm' }}; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; }
        .sheet { display: flex; flex-wrap: wrap; gap: 3mm; align-content: flex-start; }
        .label {
            border: 1px solid #555;
            padding: 1.2mm;
            width: {{ $format === 'a4' ? '60mm' : '100%' }};
            height: {{ $format === 'thermal58' ? '30mm' : ($format === 'thermal80' ? '35mm' : '35mm') }};
            display: flex;
            align-items: stretch;
            gap: 1.3mm;
            overflow: hidden;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .qr-section {
            width: 42%;
            flex: 0 0 42%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 0;
            overflow: hidden;
        }
        .qr {
            display: block;
            width: 19mm;
            height: 19mm;
            max-width: 100%;
            object-fit: contain;
        }
        .qr-caption {
            font-size: 6px;
            line-height: 1.1;
            text-align: center;
            font-weight: normal;
            margin-top: 0.6mm;
            white-space: nowrap;
        }
        .content { flex: 1 1 0; min-width: 0; overflow: hidden; }
        .brand {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.2px;
            line-height: 1.1;
            border-bottom: 1px solid #111;
            padding-bottom: 0.7mm;
            margin-bottom: 0.8mm;
        }
        .subtitle { font-size: 8px; letter-spacing: 0.3px; line-height: 1.1; margin-bottom: 1mm; }
        .name { font-size: 11px; line-height: 1.15; font-weight: bold; overflow-wrap: anywhere; max-height: 8mm; overflow: hidden; }
        .code-label { font-size: 8px; line-height: 1.1; margin-top: 1.5mm; }
        .code { font-size: 10px; line-height: 1.1; font-family: monospace; font-weight: bold; overflow-wrap: anywhere; }
        @media screen { .toolbar { padding: 12px; } }
        @media print {
            .toolbar { display: none; }
            .label { border-color: #111; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Cetak label</button></div>
    <div class="sheet">
        @for ($i = 0; $i < $copies; $i++)
            <div class="label">
                <div class="qr-section">
                    <img class="qr" src="{{ $qrDataUri }}" alt="QR identitas barang {{ $item->item_code }}">
                    <div class="qr-caption">QR KODE INVENTARIS</div>
                </div>
                <div class="content">
                    <div class="brand">SIMPRAM</div>
                    <div class="subtitle">INVENTARIS</div>
                    <div class="name">{{ $item->name }}</div>
                    <div class="code-label">KODE BARANG</div>
                    <div class="code">{{ $item->item_code }}</div>
                </div>
            </div>
        @endfor
    </div>
</body>
</html>
