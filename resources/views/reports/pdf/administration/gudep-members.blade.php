<h1 style="text-align:center;font-size:16pt;">BUKU INDUK ANGGOTA GUGUSDEPAN</h1>
<p style="text-align:center;">{{ $gudep->name }} — {{ $year?->name ?? 'Seluruh periode' }}</p>
<p style="font-size:9pt;">Daftar anggota aktif berdasarkan satuan yang ditautkan ke Gudep ini. Nomor anggota sementara menggunakan NIS dari data sekolah sampai identitas Pramuka khusus tersedia.</p>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
    <thead><tr><th>No.</th><th>Nama Anggota</th><th>NIS</th><th>Golongan</th><th>Satuan Golongan</th><th>Kelompok</th><th>Jabatan</th><th>Mulai</th></tr></thead>
    <tbody>
        @forelse ($memberRows as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['member_number'] }}</td>
                <td>{{ $row['level'] }}</td>
                <td>{{ $row['formation'] }}</td>
                <td>{{ $row['unit'] }}</td>
                <td>{{ $row['position'] }}</td>
                <td>{{ $row['joined_at'] ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="8">Belum ada anggota aktif pada satuan tertaut untuk periode ini.</td></tr>
        @endforelse
    </tbody>
</table>
<p style="font-size:8pt;margin-top:14px;">Jumlah catatan keanggotaan: {{ count($memberRows) }}. Dokumen ini berklasifikasi Terbatas dan hanya dapat diunduh oleh pengguna berizin melalui SIMPRAM.</p>
