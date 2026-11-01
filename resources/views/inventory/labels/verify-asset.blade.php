<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Aset — {{ $asset->asset_code }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 580px; margin: 3rem auto; padding: 0 1rem; line-height: 1.5; }
        .panel { border: 1px solid #ddd; border-radius: 12px; padding: 1.5rem; }
        dt { font-size: .8rem; color: #666; margin-top: 1rem; }
        dd { margin: .25rem 0 0; font-weight: 600; overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <main class="panel">
        <h1>Verifikasi Aset Inventaris</h1>
        <p>Identitas unit aset pada sekolah aktif SIMPRAM.</p>
        <dl>
            <dt>Kode aset</dt><dd>{{ $asset->asset_code }}</dd>
            <dt>Barang</dt><dd>{{ $asset->item?->name ?? '—' }}</dd>
            <dt>Nomor seri</dt><dd>{{ $asset->serial_number ?: '—' }}</dd>
            <dt>Pemilik</dt><dd>{{ $asset->owner?->display_name ?? '—' }}</dd>
            <dt>Lokasi</dt><dd>{{ $asset->location?->name ?? '—' }}</dd>
            <dt>Kondisi</dt><dd>{{ $asset->condition ?? '—' }}</dd>
            <dt>Status</dt><dd>{{ $asset->status ?? '—' }}</dd>
        </dl>
    </main>
</body>
</html>
