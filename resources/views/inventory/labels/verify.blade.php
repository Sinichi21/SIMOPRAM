<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Inventaris — {{ $item->item_code }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 580px; margin: 3rem auto; padding: 0 1rem; line-height: 1.5; }
        .panel { border: 1px solid #ddd; border-radius: 12px; padding: 1.5rem; }
        dt { font-size: .8rem; color: #666; margin-top: 1rem; }
        dd { margin: .25rem 0 0; font-weight: 600; overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <main class="panel">
        <h1>Verifikasi Barang Inventaris</h1>
        <p>Data inventaris sekolah aktif pada SIMPRAM.</p>
        <dl>
            <dt>Kode barang</dt><dd>{{ $item->item_code }}</dd>
            <dt>Nama barang</dt><dd>{{ $item->name }}</dd>
            <dt>Kategori</dt><dd>{{ $item->category?->name ?? '—' }}</dd>
            <dt>Jenis pelacakan</dt><dd>{{ $item->tracking_type === 'individual' ? 'Aset individual' : 'Kuantitas' }}</dd>
            <dt>Status</dt><dd>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</dd>
        </dl>
    </main>
</body>
</html>
