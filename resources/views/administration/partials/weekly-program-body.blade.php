{{-- The A4 weekly plan follows "Program Latihan Mingguan", page 75. --}}
@php
    $weeklyRows = $weeklyRows ?? [];
    $weeklySignatures = $weeklySignatures ?? [];
    $weeklyDate = $weeklyDate ?? null;
    $signer = $weeklySignatures['signer'] ?? null;
    $acknowledger = $weeklySignatures['acknowledger'] ?? null;
    $signerRole = $weeklySignatures['role'] ?? 'Penanggung Jawab Satuan';
@endphp
<div class="weekly-program-paper" style="font-family:'Times New Roman',Times,serif;color:#111;font-size:10pt;line-height:1.3;">
    <h2 style="text-align:center;font-size:12pt;font-weight:bold;margin:16px 0 17px;">PROGRAM LATIHAN MINGGUAN GUGUSDEPAN</h2>
    <table style="width:100%;border-collapse:collapse;table-layout:fixed;font-size:8pt;" border="1" cellpadding="4">
        <thead>
            <tr>
                <th style="width:5%;" rowspan="2">No</th>
                <th style="width:10%;" rowspan="2">Waktu</th>
                <th style="width:15%;" rowspan="2">Kegiatan</th>
                <th style="width:12%;" rowspan="2">Sasaran</th>
                <th colspan="2" style="width:14%;">Pencapaian</th>
                <th style="width:10%;" rowspan="2">Alat</th>
                <th style="width:12%;" rowspan="2">Petugas</th>
                <th style="width:13%;" rowspan="2">Acara Selingan</th>
                <th style="width:9%;" rowspan="2">KET</th>
            </tr>
            <tr>
                <th style="width:7%;">SKU</th>
                <th style="width:7%;">SKK</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($weeklyRows as $row)
                <tr style="page-break-inside:avoid;">
                    <td style="text-align:center;">{{ $loop->iteration }}</td>
                    <td>{{ $row['time'] ?? '–' }}</td>
                    <td>{{ $row['description'] ?? '–' }}</td>
                    <td>{{ $row['target'] ?? '–' }}</td>
                    <td>{{ $row['sku'] ?? '–' }}</td>
                    <td>{{ $row['skk'] ?? '–' }}</td>
                    <td>{{ $row['equipment'] ?? '–' }}</td>
                    <td>{{ $row['officer'] ?? '–' }}</td>
                    <td>{{ $row['interlude'] ?? '–' }}</td>
                    <td>{{ $row['notes'] ?? '–' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="padding:24px;text-align:center;">Belum ada kegiatan latihan.</td></tr>
            @endforelse
        </tbody>
    </table>
    <table style="width:100%;border:0;margin-top:23px;font-size:10pt;" cellpadding="2">
        <tr>
            <td style="width:50%;vertical-align:top;">
                <div>Mengetahui,</div>
                <div>Pembina Gugus Depan,</div>
                <div style="height:48px;"></div>
                <div>( {{ $acknowledger['name'] ?? '....................................' }} )</div>
                @if (filled($acknowledger['identity'] ?? null))
                    <div style="font-size:8pt;">{{ $acknowledger['identity'] }}</div>
                @endif
                <div style="font-size:8pt;">{{ $acknowledger['position'] ?? 'Pembina Gugus Depan' }}</div>
            </td>
            <td style="width:50%;vertical-align:top;">
                <div>{{ $weeklyDate ?: '.........................' }}</div>
                <div>{{ $signerRole }},</div>
                <div style="height:48px;"></div>
                <div>( {{ $signer['name'] ?? '....................................' }} )</div>
                @if (filled($signer['identity'] ?? null))
                    <div style="font-size:8pt;">{{ $signer['identity'] }}</div>
                @endif
                <div style="font-size:8pt;">{{ $signer['position'] ?? $signerRole }}</div>
            </td>
        </tr>
    </table>
    <div style="margin-top:20px;font-size:9pt;">
        <strong>Catatan penandatangan:</strong>
        Siaga: Pembina; Penggalang: Pratama; Penegak: Pradana; Pandega: Ketua Racana.
    </div>
</div>
