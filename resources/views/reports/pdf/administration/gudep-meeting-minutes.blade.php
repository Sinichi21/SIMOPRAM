<h1 style="font-size:15pt;text-align:center;">BERITA ACARA / NOTULEN RAPAT GUGUSDEPAN</h1>
<p style="text-align:center;">{{ $gudep->name }} — Tahun Ajaran {{ $year->name }}</p>
<table style="width:100%;font-size:9pt;margin-top:10px;">
    <tr><td width="28%">Nama rapat</td><td>: {{ $meeting->title }}</td></tr>
    <tr><td>Hari / tanggal</td><td>: {{ $meeting->held_at?->format('d/m/Y H:i') }}</td></tr>
    <tr><td>Tempat</td><td>: {{ $meeting->location ?: '-' }}</td></tr>
    <tr><td>Pimpinan</td><td>: {{ $meeting->chairperson ?: '-' }}</td></tr>
    <tr><td>Notulis</td><td>: {{ $meeting->minute_taker ?: '-' }}</td></tr>
    <tr><td>Peserta</td><td>: {{ $meeting->attendees ?: '-' }}</td></tr>
</table>
<h2 style="font-size:11pt;margin-top:16px;">Agenda</h2>
<p style="white-space:pre-wrap;">{{ $meeting->agenda ?: '-' }}</p>
<h2 style="font-size:11pt;margin-top:16px;">Uraian Notulen</h2>
<p style="white-space:pre-wrap;">{{ $meeting->minutes }}</p>
<h2 style="font-size:11pt;margin-top:16px;">Keputusan dan Tindak Lanjut</h2>
<table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
    <thead><tr><th>No.</th><th>Keputusan</th><th>Penanggung Jawab</th><th>Tenggat</th><th>Status</th></tr></thead>
    <tbody>
        @forelse ($decisions as $decision)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $decision->decision }}</td>
                <td>{{ $decision->person_responsible ?: '-' }}</td>
                <td>{{ $decision->due_at?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $decision->status === 'done' ? 'Selesai' : 'Terbuka' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Tidak ada keputusan yang dicatat.</td></tr>
        @endforelse
    </tbody>
</table>
<p style="font-size:8pt;margin-top:15px;">Notulen difinalisasi {{ $meeting->finalized_at?->format('d/m/Y H:i') }}. Dokumen bersifat terbatas dan dapat diverifikasi melalui kode QR arsip.</p>
