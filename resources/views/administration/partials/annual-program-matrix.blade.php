@php
    $months = [8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6, 7];
    $groups = [
        'siaga' => 'Siaga',
        'penggalang' => 'Penggalang',
        'penegak' => 'Penegak',
        'pandega' => 'Pandega',
    ];
    $sections = [
        'bersama' => ['II', 'BIDANG KEGIATAN BERSAMA ANTAR SATUAN DALAM GUGUSDEPAN/ANTAR GUDEP'],
        'dewasa' => ['III', 'BIDANG PENDIDIKAN ORANG DEWASA'],
        'penghargaan' => ['IV', 'BIDANG TANDA PENGHARGAAN'],
        'sarana' => ['V', 'BIDANG SARANA DAN ADMINISTRASI'],
    ];
    $money = \App\Support\GudepAnnualProgram::totals($entries);
    $financialRows = \App\Support\GudepAnnualProgram::numbered($entries, 'keuangan');
    $financialGroups = ['income' => [], 'expense' => [], 'fundraising' => []];
    foreach ($financialRows as $financialRow) {
        $financeType = $financialRow['entry']['finance_type'] ?? '';
        if (isset($financialGroups[$financeType])) {
            $financialGroups[$financeType][] = $financialRow;
        }
    }
@endphp
<section style="font-family:'Times New Roman',Times,serif;font-size:8.5pt;">
    <div style="text-align:center;font-size:12pt;font-weight:bold;margin:13px 0 2px;">PROGRAM KERJA TAHUNAN</div>
    <div style="text-align:center;font-size:10pt;font-weight:bold;">GUGUSDEPAN {{ mb_strtoupper(trim(($kwarcab ?? '').' '.($programNumber ?? '–'))) }}</div>
    @if (filled($formationName ?? null))
        <div style="text-align:center;font-size:9pt;font-weight:bold;">{{ mb_strtoupper($formationName) }}</div>
    @endif
    <p style="margin:12px 0 11px;font-size:9pt;font-weight:bold;">TAHUN ANGGARAN : {{ $yearName ?? '–' }}</p>
    <table border="1" cellpadding="2" style="width:100%;table-layout:fixed;border-collapse:collapse;font-size:8.2pt;line-height:1.22;">
        <colgroup>
            <col style="width:4%;">
            <col style="width:43%;">
            @foreach ($months as $month)
                <col style="width:2.5%;">
            @endforeach
            <col style="width:12%;">
            <col style="width:11%;">
        </colgroup>
        <thead style="display:table-header-group;">
            <tr style="background:#d5d5d5;font-weight:bold;">
                <th rowspan="2">NO</th>
                <th rowspan="2">BIDANG KEGIATAN</th>
                <th colspan="12" style="text-align:center;letter-spacing:1px;">B U L A N</th>
                <th rowspan="2" style="text-align:center;">JUMLAH<br>S/G/T/D *)</th>
                <th rowspan="2">KETERANGAN</th>
            </tr>
            <tr style="background:#e8e8e8;">
                @foreach ($months as $month)
                    <th style="text-align:center;">{{ $month }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $hasYouth = collect($entries)->contains(fn (array $entry): bool => in_array($entry['section'] ?? '', array_keys($groups), true));
            @endphp
            @if ($hasYouth)
                <tr style="page-break-inside:avoid;background:#dedede;font-weight:bold;">
                    <td style="text-align:center;">I</td>
                    <td>KEGIATAN DAN LATIHAN PESERTA DIDIK</td>
                    <td colspan="14"></td>
                </tr>
                @foreach ($groups as $section => $group)
                    @php $rows = \App\Support\GudepAnnualProgram::numbered($entries, $section); @endphp
                    @if ($rows !== [])
                        <tr style="page-break-inside:avoid;">
                            <td></td>
                            <td style="font-weight:bold;">{{ $loop->iteration }}. {{ $group }}</td>
                            <td colspan="14"></td>
                        </tr>
                        @foreach ($rows as $row)
                            @php $item = $row['entry']; @endphp
                            <tr style="page-break-inside:avoid;">
                                <td></td>
                                <td style="padding-left:{{ 6 + $row['depth'] * 11 }}px;">
                                    {{ $row['marker'] }} {{ $item['description'] ?? '–' }}
                                    @if (filled($item['detail'] ?? null))
                                        <div style="font-size:6.7pt;">{{ $item['detail'] }}</div>
                                    @endif
                                </td>
                                @foreach ($months as $monthIndex => $month)
                                    <td style="text-align:center;">{{ ($item['months'][$monthIndex] ?? false) ? 'X' : '' }}</td>
                                @endforeach
                                <td style="text-align:center;">{{ ($item['quantity'] ?? null) === null ? '' : $item['quantity'] }}</td>
                                <td>{{ $item['notes'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            @endif
            @foreach ($sections as $section => [$numeral, $heading])
                @php $rows = \App\Support\GudepAnnualProgram::numbered($entries, $section); @endphp
                @if ($rows !== [])
                    <tr style="page-break-inside:avoid;background:#dedede;font-weight:bold;">
                        <td style="text-align:center;">{{ $numeral }}</td>
                        <td>{{ $heading }}</td>
                        <td colspan="14"></td>
                    </tr>
                    @foreach ($rows as $row)
                        @php $item = $row['entry']; @endphp
                        <tr style="page-break-inside:avoid;">
                            <td></td>
                            <td style="padding-left:{{ 6 + $row['depth'] * 11 }}px;">
                                {{ $row['marker'] }} {{ $item['description'] ?? '–' }}
                                @if (filled($item['detail'] ?? null))
                                    <div style="font-size:6.7pt;">{{ $item['detail'] }}</div>
                                @endif
                            </td>
                            @foreach ($months as $monthIndex => $month)
                                <td style="text-align:center;">{{ ($item['months'][$monthIndex] ?? false) ? 'X' : '' }}</td>
                            @endforeach
                            <td style="text-align:center;">{{ ($item['quantity'] ?? null) === null ? '' : $item['quantity'] }}</td>
                            <td>{{ $item['notes'] ?? '' }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach

            {{-- Bidang VI occupies the very same table footprint. All twelve month
                 columns are merged into the description, not rendered as a calendar. --}}
            <tr style="background:#d5d5d5;font-weight:bold;page-break-inside:avoid;">
                <td style="text-align:center;">VI</td>
                <td colspan="13">BIDANG KEUANGAN</td>
                <td style="text-align:center;">JUMLAH<br>(Rp,-)</td>
                <td>KETERANGAN</td>
            </tr>
            @foreach ([
                ['title' => '1. Rencana Pemasukan', 'type' => 'income', 'total' => $money['income'], 'color' => '#e3eddc'],
                ['title' => '2. Rencana Pengeluaran', 'type' => 'expense', 'total' => $money['expense'], 'color' => '#fae0c9'],
            ] as $group)
                <tr style="background:#ececec;page-break-inside:avoid;">
                    <td></td><td colspan="14">{{ $group['title'] }}</td><td></td>
                </tr>
                @foreach ($financialGroups[$group['type']] as $row)
                    @php $item = $row['entry']; @endphp
                    <tr style="page-break-inside:avoid;">
                        <td></td>
                        <td colspan="13" style="padding-left:18px;">{{ $row['marker'] }} {{ $item['description'] ?? '' }}</td>
                        <td style="text-align:right;white-space:nowrap;">{{ ($item['amount'] ?? null) === null ? '–' : number_format((int) $item['amount'], 0, ',', '.') }}</td>
                        <td>{{ $item['notes'] ?? '' }}</td>
                    </tr>
                @endforeach
                <tr style="page-break-inside:avoid;font-weight:bold;background:{{ $group['color'] }};">
                    <td></td><td colspan="13" style="padding-left:18px;">Jumlah</td>
                    <td style="text-align:right;white-space:nowrap;">{{ number_format($group['total'], 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            @endforeach
            <tr style="page-break-inside:avoid;font-weight:bold;background:#f1d9d9;">
                <td></td><td colspan="13" style="padding-left:18px;">Selisih RKAG (Rencana Pemasukan - Rencana Pengeluaran)</td>
                <td style="text-align:right;white-space:nowrap;">{{ number_format($money['expense'] - $money['income'], 0, ',', '.') }}</td>
                <td></td>
            </tr>
            @php
                // The original form stores an 'Usaha Dana' title and explanatory
                // sentences separately. Show one concise computed summary and
                // retain only the actual fundraising method entered by users.
                $fundraisingDetails = [];
                foreach ($financialGroups['fundraising'] as $row) {
                    $item = $row['entry'];
                    foreach ([(string) ($item['description'] ?? ''), (string) ($item['detail'] ?? ''), (string) ($item['notes'] ?? '')] as $part) {
                        $part = trim($part);
                        // Preserve the named activity even when a legacy
                        // paragraph begins with the redundant shortage sentence.
                        if (preg_match('/sumber usaha dana (?:dari|melalui) (.+)$/iu', $part, $detailMatch)) {
                            $part = trim($detailMatch[1]);
                        } elseif (preg_match('/dengan mengadakan (.+)$/iu', $part, $detailMatch)) {
                            $part = trim($detailMatch[1]);
                        }
                        if ($part === '' || preg_match('/^usaha dana\\.?$/iu', $part)
                            || preg_match('/^kekurangan rencana biaya/i', $part)
                            || preg_match('/^kekurangan biaya/i', $part)
                            || preg_match('/^diupayakan melalui usaha dana/i', $part)) {
                            continue;
                        }
                        $fundraisingDetails[] = $part;
                    }
                }
                $fundraisingDetails = array_values(array_unique($fundraisingDetails));
            @endphp
            <tr style="page-break-inside:avoid;background:#ececec;font-weight:bold;">
                <td></td><td colspan="15">3. Usaha Dana</td>
            </tr>
            <tr style="page-break-inside:avoid;">
                <td></td>
                <td colspan="15" style="padding:5px 9px 6px 18px;line-height:1.35;">
                    @if ($money['shortfall'] > 0)
                        Kekurangan rencana biaya sebesar Rp {{ number_format($money['shortfall'], 0, ',', '.') }} diupayakan melalui usaha dana.
                    @else
                        Tidak terdapat kekurangan rencana biaya.
                    @endif
                    @if ($fundraisingDetails !== [])
                        <div style="margin-top:3px;">{{ implode('; ', $fundraisingDetails) }}</div>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</section>
