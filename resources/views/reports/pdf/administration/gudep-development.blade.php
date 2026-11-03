<h1 style="text-align:center;font-size:16pt;">PORTOFOLIO PEMBINAAN GUGUSDEPAN</h1>
<p style="text-align:center;">{{ $gudep->name }} — Tahun Ajaran {{ $year->name }}</p>
<p style="font-size:9pt;">Dokumen internal terbatas. Identitas anggota menggunakan NTA; NIS tetap disimpan di master sekolah dan tidak diperlukan dalam portofolio ini.</p>
<h2 style="font-size:11pt;margin-top:18px;">A. Program Kerja</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
    <thead><tr><th>No.</th><th>Nama Program</th><th>Rencana</th><th>Tujuan</th><th>Status</th><th>Agenda</th></tr></thead>
    <tbody>
    @forelse ($programs as $row)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $row->title }}</td><td>{{ $row->planned_for?->format('d/m/Y') ?? '-' }}</td>
            <td>{{ $row->objective ?: '-' }}</td><td>{{ $row->status }}</td><td>{{ $row->activity?->title ?? '-' }}</td></tr>
    @empty
        <tr><td colspan="6">Belum ada program kerja.</td></tr>
    @endforelse
    </tbody>
</table>
<h2 style="font-size:11pt;margin-top:18px;">B. Progres Kecakapan SKU/SKK</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
    <thead><tr><th>No.</th><th>Anggota</th><th>NTA</th><th>Golongan</th><th>Persyaratan</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($competencies as $row)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $row->student?->name ?? '-' }}</td><td>{{ $row->student?->nta ?: '-' }}</td><td>{{ $row->scoutLevel?->name ?? '-' }}</td>
            <td>{{ $row->category }} {{ $row->requirement_code }} — {{ $row->requirement_name }}</td><td>{{ $row->status }}</td></tr>
    @empty
        <tr><td colspan="6">Belum ada catatan kecakapan.</td></tr>
    @endforelse
    </tbody>
</table>
<h2 style="font-size:11pt;margin-top:18px;">C. Prestasi, TKK & Pengembangan Pembina</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
    <thead><tr><th>No.</th><th>Tanggal</th><th>Jenis</th><th>Prestasi/Pelatihan</th><th>Penerima</th><th>Referensi</th></tr></thead><tbody>
    @forelse ($achievements as $row)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $row->awarded_at?->format('d/m/Y') }}</td><td>{{ $row->kind }}</td><td>{{ $row->title }}</td>
            <td>{{ $row->student?->name ?? $row->coach?->name ?? 'Gudep' }}</td><td>{{ $row->reference_number ?: '-' }}</td></tr>
    @empty
        <tr><td colspan="6">Belum ada prestasi/pengembangan.</td></tr>
    @endforelse
    </tbody>
</table>
<h2 style="font-size:11pt;margin-top:18px;">D. Jurnal Pelaksanaan Program yang Ditautkan</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
    <thead><tr><th>No.</th><th>Agenda</th><th>Materi</th><th>Hasil</th><th>Evaluasi / Tindak Lanjut</th></tr></thead><tbody>
    @forelse ($journals as $row)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $row->activity?->title ?? '-' }}</td><td>{{ $row->material ?: '-' }}</td>
            <td>{{ $row->result ?: '-' }}</td><td>{{ $row->evaluation ?: '-' }} / {{ $row->follow_up ?: '-' }}</td></tr>
    @empty
        <tr><td colspan="5">Belum ada jurnal dari agenda yang ditautkan ke program.</td></tr>
    @endforelse
    </tbody>
</table>
<p style="font-size:8pt;margin-top:16px;">Rincian persyaratan harus sesuai SKU/SKK resmi. Dokumen ini adalah portofolio internal, bukan sertifikat tanda kecakapan.</p>
