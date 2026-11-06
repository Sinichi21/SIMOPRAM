<h1 style="text-align:center;font-size:15pt;margin-bottom:4px;">PROGRAM KERJA {{ ['annual' => 'TAHUNAN', 'semester' => 'SEMESTER', 'weekly' => 'LATIHAN MINGGUAN'][$snapshot['kind']] ?? 'GUGUSDEPAN' }}</h1>
<p style="text-align:center;font-size:10pt;margin-top:0;">{{ $snapshot['gudep_name'] }} — {{ $snapshot['year_name'] }}</p>
<table style="width:100%;font-size:9pt;margin:12px 0;border-collapse:collapse;">
    <tr><td style="width:28%;">Judul program</td><td>: {{ $snapshot['title'] }}</td></tr>
    <tr><td>Nomor Gudep</td><td>: Putra {{ $snapshot['gudep_male_number'] ?: '–' }} / Putri {{ $snapshot['gudep_female_number'] ?: '–' }}</td></tr>
    <tr><td>Tahun ajaran</td><td>: {{ $snapshot['year_name'] }}</td></tr>
    @if ($snapshot['kind'] !== 'annual')
        <tr><td>Semester</td><td>: {{ $snapshot['semester_name'] ?: '–' }}</td></tr>
        <tr><td>Golongan</td><td>: {{ $snapshot['level_name'] ?: '–' }}</td></tr>
    @endif
    @if ($snapshot['kind'] === 'weekly')
        <tr><td>Tanggal latihan</td><td>: {{ $snapshot['scheduled_for'] ?: '–' }}</td></tr>
        <tr><td>Agenda</td><td>: {{ $snapshot['activity_title'] ?: '–' }}</td></tr>
        <tr><td>Lokasi</td><td>: {{ $snapshot['activity_location'] ?: '–' }}</td></tr>
    @endif
</table>
@if ($snapshot['kind'] === 'annual')
    @foreach ($sections as $key => $heading)
        @php $rows = collect($snapshot['entries'])->where('section', $key)->values(); @endphp
        <h2 style="font-size:10pt;margin:14px 0 6px;">{{ $heading }}</h2>
        <table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
            <thead><tr><th width="8%">No.</th><th>Uraian Program/Kegiatan</th><th width="15%">Jumlah</th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr><td style="text-align:center;">{{ $loop->iteration }}.</td><td>{{ $row['description'] ?? '–' }}</td><td style="text-align:center;">{{ $row['quantity'] ?? '–' }}</td></tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;">–</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
@elseif ($snapshot['kind'] === 'semester')
    <h2 style="font-size:10pt;">Rencana Kegiatan Semester</h2>
    <table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="4">
        <thead><tr><th>No.</th><th>Kegiatan</th>
            @for ($month=1; $month<=6; $month++)<th>{{ $month }}</th>@endfor
            <th>Jumlah Anggota</th><th>Ket.</th>
        </tr></thead>
        <tbody>
            @foreach ($snapshot['entries'] as $row)
                <tr><td>{{ $loop->iteration }}.</td><td>{{ $row['description'] ?? '–' }}</td>
                    @for ($month=0; $month<6; $month++)<td>{{ ($row['months'][$month] ?? false) ? 'X' : '–' }}</td>@endfor
                    <td>{{ $row['participants'] ?? '–' }}</td><td>{{ $row['notes'] ?? '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <h2 style="font-size:10pt;">Rencana Pelaksanaan Latihan</h2>
    <table style="width:100%;border-collapse:collapse;font-size:8pt;" border="1" cellpadding="5">
        <thead><tr><th>No.</th><th>Materi Latihan</th><th>SKU</th><th>SKK</th><th>Ket.</th></tr></thead>
        <tbody>
            @foreach ($snapshot['entries'] as $row)
                <tr><td>{{ $loop->iteration }}.</td><td>{{ $row['description'] ?? '–' }}</td><td>{{ $row['sku'] ?: '–' }}</td><td>{{ $row['skk'] ?: '–' }}</td><td>{{ $row['notes'] ?: '–' }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif
@if ($snapshot['notes'])
    <h2 style="font-size:10pt;">Catatan</h2>
    <p style="font-size:9pt;white-space:pre-line;">{{ $snapshot['notes'] }}</p>
@endif
<p style="font-size:8pt;margin-top:22px;">Dokumen ini memuat snapshot program yang diterbitkan oleh SIMPRAM. Perubahan selanjutnya pada program atau agenda tidak mengubah arsip versi ini. QR verifikasi, nomor revisi, dan metadata penerbitan disertakan melalui sistem penerbitan dokumen.</p>
