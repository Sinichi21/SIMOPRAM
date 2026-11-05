<h1 style="font-size:15pt;text-align:center;">BUKU TAMU GUGUSDEPAN</h1>
<p style="text-align:center;">{{ $gudep->name }} — Tahun Ajaran {{ $year->name }}</p>
<p style="font-size:8pt;">Arsip internal terbatas. Kunjungan dihimpun sesuai tahun ajaran yang dipilih.</p>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
    <thead><tr><th>No.</th><th>Tanggal & waktu</th><th>Nama tamu</th><th>Asal/Instansi</th><th>Keperluan</th><th>Diterima oleh</th></tr></thead>
    <tbody>
        @forelse ($entries as $entry)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $entry->visited_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $entry->guest_name }}</td>
                <td>{{ $entry->organization ?: '-' }}</td>
                <td>{{ $entry->purpose }}</td>
                <td>{{ $entry->received_by ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Belum ada kunjungan tercatat.</td></tr>
        @endforelse
    </tbody>
</table>
<p style="margin-top:15px;font-size:8pt;">Jumlah kunjungan: {{ $entries->count() }}. Kode QR memverifikasi arsip penerbitan, bukan mengungkap data tamu kepada publik.</p>
