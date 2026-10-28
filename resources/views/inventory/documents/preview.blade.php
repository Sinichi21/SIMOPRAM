<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Draft Berita Acara Inventaris</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 15px; text-align: center; margin-bottom: 3px; }
        h2 { font-size: 11px; margin-top: 22px; }
        .subtitle { text-align: center; margin-bottom: 20px; }
        .draft { color: #b91c1c; font-weight: bold; text-align: center; font-size: 12px; margin: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #555; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #eee; }
        .signatures { margin-top: 55px; width: 100%; }
        .signatures td { border: none; width: 50%; text-align: center; }
        .space { height: 65px; }
        .small { font-size: 9px; color: #555; }
    </style>
</head>
<body>
    <div class="draft">DRAFT — BELUM DITERBITKAN / BELUM DITANDATANGANI</div>
    <h1>BERITA ACARA {{ $type === 'handover' ? 'SERAH-TERIMA' : 'PENGEMBALIAN' }} INVENTARIS</h1>
    <div class="subtitle">Referensi peminjaman: {{ $loan->loan_number }}</div>
    <p><strong>Nama peminjam:</strong> {{ $loan->borrower_name }}</p>
    <p><strong>Instansi/organisasi:</strong> {{ $loan->borrower_organization ?: '-' }}</p>
    <p><strong>Kontak:</strong> {{ $loan->borrower_contact ?: '-' }}</p>
    <p><strong>Tujuan:</strong> {{ $loan->purpose ?: '-' }}</p>
    <p><strong>Waktu kejadian:</strong> {{ $events->first()?->occurred_at?->format('d-m-Y H:i') ?: '-' }} (zona waktu aplikasi)</p>
    <h2>Rincian transaksi</h2>
    <table>
        <thead>
            <tr><th>No.</th><th>Barang</th><th>Pemilik</th><th>Lokasi asal</th><th>Waktu</th><th>Jumlah</th><th>Kondisi</th><th>Catatan</th></tr>
        </thead>
        <tbody>
            @foreach ($events as $event)
                @php($line = $loan->items->firstWhere('id', $event->loan_item_id))
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $line?->item?->name ?? '-' }}</td>
                    <td>{{ $line?->owner?->display_name ?? '-' }}</td>
                    <td>{{ $line?->location?->name ?? '-' }}</td>
                    <td>{{ $event->occurred_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $event->quantity ?? '-' }}</td>
                    <td>{{ $event->condition ?? '-' }}</td>
                    <td>{{ $event->notes ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="small">Jumlah dan kondisi mengikuti event yang dicatat, bukan total permohonan. Transaksi dapat terjadi bertahap; setiap baris menunjukkan satu event. Untuk pengembalian, nilai jumlah mengikuti catatan event sistem.</p>
    <p class="small">Dokumen ini merupakan pratinjau dari catatan transaksi SIMPRAM, bukan berita acara resmi. Untuk transaksi pengembalian dengan selisih/hilang, periksa rincian pada sistem sebelum menerbitkan berita acara final.</p>
    <table class="signatures">
        <tr>
            <td>Petugas / Pihak yang menyerahkan<br><div class="space"></div>(........................................)</td>
            <td>Peminjam / Pihak yang menerima<br><div class="space"></div>({{ $loan->borrower_name }})</td>
        </tr>
    </table>
</body>
</html>
