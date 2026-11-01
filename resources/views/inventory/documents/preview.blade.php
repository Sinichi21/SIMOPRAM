<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ empty($issued) ? 'Draft' : $documentNumber }} Berita Acara Inventaris</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 16mm 27mm; }
        body { margin: 0; font-family: "Times New Roman", Times, serif; font-size: 10pt; color: #222; }
        .letterhead { border-bottom: 1.6px solid #000; margin-bottom: 4mm; min-height: 22mm; padding: 0 2mm 2.5mm; position: relative; text-align: center; }
        .letterhead-logo-left, .letterhead-logo-right { height: 21mm; max-width: 23mm; position: absolute; top: 0; }
        .letterhead-logo-left { left: 1mm; }
        .letterhead-logo-right { right: 1mm; }
        .letterhead-title { font-size: 13pt; font-weight: bold; line-height: 1.05; margin: 0 23mm; }
        .letterhead-address { font-size: 9pt; line-height: 1.1; margin: .8mm 23mm 0; }
        .identity { width: 178mm; margin: 0; }
        .identity td { border: none; padding: 2px 0; overflow-wrap: break-word; }
        .identity .label { width: 39mm; font-weight: bold; }
        .identity .colon { width: 4mm; text-align: center; }
        .transactions { font-size: 8pt; }
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
    @include('reports.pdf.lpj.partials.letterhead')
    @include('reports.pdf.partials.verification-footer')
    @if (empty($issued))
        <div class="draft">DRAFT — BELUM DITERBITKAN / BELUM DITANDATANGANI</div>
    @endif
    <h1>BERITA ACARA {{ $type === 'handover' ? 'SERAH-TERIMA' : 'PENGEMBALIAN' }} INVENTARIS</h1>
    @if (! empty($issued))
        <div style="text-align: center; margin: 3px 0 6px;"><strong>Nomor: {{ $documentNumber }}</strong></div>
    @endif
    <div class="subtitle">Referensi peminjaman: {{ $loan->loan_number }}</div>
    @if (! empty($correctionReason))
        <p><strong>Koreksi dari BA:</strong> {{ $previousDocumentNumber ?? '-' }}</p>
        <p><strong>Alasan koreksi:</strong> {{ $correctionReason }}</p>
        <p><strong>Diterbitkan oleh:</strong> {{ $correctionIssuedBy ?? '-' }} | <strong>Waktu penerbitan:</strong> {{ $correctionIssuedAt ?? '-' }}</p>
    @endif
    <table class="identity">
        <tr><td class="label" style="width: 39mm;">Nama peminjam</td><td class="colon" style="width: 4mm;">:</td><td style="width: 135mm;">{{ $loan->borrower_name }}</td></tr>
        <tr><td class="label" style="width: 39mm;">Instansi/organisasi</td><td class="colon" style="width: 4mm;">:</td><td style="width: 135mm;">{{ $loan->borrower_organization ?: '-' }}</td></tr>
        <tr><td class="label" style="width: 39mm;">Kontak</td><td class="colon" style="width: 4mm;">:</td><td style="width: 135mm;">{{ $loan->borrower_contact ?: '-' }}</td></tr>
        <tr><td class="label" style="width: 39mm;">Tujuan</td><td class="colon" style="width: 4mm;">:</td><td style="width: 135mm;">{{ $loan->purpose ?: '-' }}</td></tr>
        <tr><td class="label" style="width: 39mm;">Waktu kejadian</td><td class="colon" style="width: 4mm;">:</td><td style="width: 135mm;">{{ $events->first()?->occurred_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') ?: '-' }} ({{ config('app.timezone') }})</td></tr>
    </table>
    <h2>Rincian transaksi</h2>
    <table class="transactions">
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
                    <td>{{ $event->occurred_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $event->quantity === null ? '-' : number_format((float) $event->quantity, 0, '', '') }}</td>
                    <td>{{ $event->condition ?? '-' }}</td>
                    <td>{{ $event->notes ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="small">Jumlah dan kondisi mengikuti event yang dicatat, bukan total permohonan. Transaksi dapat terjadi bertahap; setiap baris menunjukkan satu event. Untuk pengembalian, nilai jumlah mengikuti catatan event sistem.</p>
    @if ($events->contains(fn ($event) => $event->evidence->isNotEmpty()))
        <h2>Lampiran bukti foto kondisi barang</h2>
        <p class="small">Referensi foto tersimpan secara privat dalam SIMPRAM. Kode SHA-256 mengikat berkas foto yang dicatat pada saat dokumen ini diterbitkan; foto asli dapat diperiksa melalui rincian transaksi dengan izin yang sesuai.</p>
        <table>
            <thead><tr><th>Event</th><th>Waktu kejadian</th><th>ID bukti</th><th>SHA-256 foto</th></tr></thead>
            <tbody>
                @foreach ($events as $event)
                    @foreach ($event->evidence as $photo)
                        <tr>
                            <td>#{{ $event->id }} — {{ $event->event_type }}</td>
                            <td>{{ $event->occurred_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>#{{ $photo->id }}
                                @if (! empty($evidenceThumbnails[$photo->id]))
                                    <div style="margin-top: 5px;"><img src="{{ $evidenceThumbnails[$photo->id] }}" alt="Foto kondisi barang" style="max-width: 160px; max-height: 110px;" /></div>
                                @endif
                            </td>
                            <td style="font-size: 7px; word-break: break-all;">{{ $photo->sha256 }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif
    @if (empty($issued))
        <p class="small">Dokumen ini merupakan pratinjau dari catatan transaksi SIMPRAM, bukan berita acara resmi. Untuk transaksi pengembalian dengan selisih/hilang, periksa rincian pada sistem sebelum menerbitkan berita acara final.</p>
    @else
        <p class="small">Dokumen diterbitkan oleh SIMPRAM sebagai salinan tetap dari transaksi yang tercatat. QR memvalidasi penerbitan dokumen, bukan tanda tangan basah. Kolom tanda tangan harus ditandatangani oleh pihak terkait sebelum menjadi bukti serah-terima yang lengkap.</p>
    @endif
    <table class="signatures">
        <tr>
            <td>Petugas / Pihak yang menyerahkan<br><div class="space"></div>(........................................)</td>
            <td>Peminjam / Pihak yang menerima<br><div class="space"></div>({{ $loan->borrower_name }})</td>
        </tr>
    </table>
</body>
</html>
