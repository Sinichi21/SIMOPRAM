@php
    $months = [8,9,10,11,12,1,2,3,4,5,6,7];
    $sections = \App\Support\GudepAnnualProgram::sections();
    $labels = [
        'siaga' => 'I. KEGIATAN DAN LATIHAN PESERTA DIDIK — SIAGA',
        'penggalang' => 'I. KEGIATAN DAN LATIHAN PESERTA DIDIK — PENGGALANG',
        'penegak' => 'I. KEGIATAN DAN LATIHAN PESERTA DIDIK — PENEGAK',
        'pandega' => 'I. KEGIATAN DAN LATIHAN PESERTA DIDIK — PANDEGA',
        'bersama' => 'II. BIDANG KEGIATAN BERSAMA ANTAR SATUAN',
        'dewasa' => 'III. BIDANG PENDIDIKAN ORANG DEWASA',
        'penghargaan' => 'IV. BIDANG TANDA PENGHARGAAN',
        'sarana' => 'V. BIDANG SARANA DAN ADMINISTRASI',
        'keuangan' => 'VI. BIDANG KEUANGAN',
    ];
@endphp
<section style="font-family:'Times New Roman',Times,serif;font-size:9pt;">
    <div style="font-weight:bold;text-align:center;font-size:12pt;margin:14px 0 3px;">PROGRAM KERJA TAHUNAN</div>
    <div style="font-weight:bold;text-align:center;font-size:10pt;">GUGUSDEPAN {{ $programNumber ?? '–' }}</div>
    <div style="text-align:center;font-size:9pt;margin-bottom:12px;">TAHUN ANGGARAN: {{ $yearName ?? '–' }}</div>
    <table border="1" cellpadding="3" style="border-collapse:collapse;width:100%;table-layout:fixed;font-size:7.3pt;">
        <thead style="display:table-header-group;">
            <tr style="background-color:#e5e5e5;">
                <th rowspan="2" style="width:5%;">NO</th>
                <th rowspan="2" style="width:34%;">BIDANG KEGIATAN</th>
                <th colspan="12" style="width:36%;">B U L A N</th>
                <th rowspan="2" style="width:12%;">JUMLAH</th>
                <th rowspan="2" style="width:13%;">KETERANGAN</th>
            </tr>
            <tr style="background-color:#eee;">
                @foreach ($months as $month)
                    <th style="width:3%;">{{ $month }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (array_keys($labels) as $section)
                @php $rows = \App\Support\GudepAnnualProgram::numbered($entries, $section); @endphp
                @if ($rows !== [])
                    <tr style="page-break-inside:avoid;background-color:#f3f3f3;font-weight:bold;">
                        <td style="text-align:center;">{{ $section === 'bersama' ? 'II' : ($section === 'dewasa' ? 'III' : ($section === 'penghargaan' ? 'IV' : ($section === 'sarana' ? 'V' : ($section === 'keuangan' ? 'VI' : 'I')))) }}</td>
                        <td>{{ $labels[$section] }}</td>
                        <td colspan="14"></td>
                    </tr>
                    @foreach ($rows as $row)
                        @php $item = $row['entry']; @endphp
                        <tr style="page-break-inside:avoid;">
                            <td></td>
                            <td style="padding-left:{{ 4 + $row['depth'] * 9 }}px;">
                                {{ $row['marker'] }} {{ $item['description'] ?? '–' }}
                                @if (filled($item['detail'] ?? null))
                                    <div style="font-size:7pt;">{{ $item['detail'] }}</div>
                                @endif
                            </td>
                            @foreach ($months as $monthIndex => $month)
                                <td style="text-align:center;">{{ ($item['months'][$monthIndex] ?? false) ? 'X' : '' }}</td>
                            @endforeach
                            <td style="text-align:right;">
                                @if (($item['finance_type'] ?? '') === 'income' || ($item['finance_type'] ?? '') === 'expense')
                                    Rp {{ number_format((int) ($item['amount'] ?? 0), 0, ',', '.') }}
                                @elseif (($item['quantity'] ?? null) !== null)
                                    {{ $item['quantity'] }} {{ $item['unit'] ?? 'kali' }}
                                @endif
                            </td>
                            <td>{{ $item['notes'] ?? '' }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
    @include('administration.partials.annual-program-finance', ['entries' => $entries])
</section>
