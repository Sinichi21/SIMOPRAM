@php
    $financeRows = \App\Support\GudepAnnualProgram::numbered($entries, 'keuangan');
    $financial = \App\Support\GudepAnnualProgram::totals($entries);
@endphp
<div style="font-family:'Times New Roman',Times,serif;font-size:10pt;page-break-inside:auto;">
    <div style="font-weight:bold;margin:16px 0 6px;">F. BIDANG KEUANGAN</div>
    <table style="width:100%;border-collapse:collapse;table-layout:fixed;font-size:10pt;">
        <colgroup><col style="width:68%;"><col style="width:32%;"></colgroup>
        <tbody>
            @forelse ($financeRows as $row)
                @php
                    $item = $row['entry'];
                    $type = $item['finance_type'] ?? '';
                    $description = (string) ($item['description'] ?? '–');
                @endphp
                <tr style="page-break-inside:avoid;">
                    <td style="vertical-align:top;padding:3px 3px 3px {{ 18 + ($row['depth'] * 21) }}px;">
                        <span style="display:inline-block;width:21px;">{{ $row['marker'] }}</span>{{ $description }}
                        @if (filled($item['detail'] ?? null))
                            <div style="margin-left:21px;white-space:pre-line;font-size:9pt;">{{ $item['detail'] }}</div>
                        @endif
                        @if (! empty($item['rkag_lines']))
                            <div style="margin:4px 0 2px 21px;font-size:8pt;">
                                @foreach ($item['rkag_lines'] as $line)
                                    <div style="page-break-inside:avoid;">
                                        {{ $line['description'] ?? '' }} —
                                        {{ number_format((int) ($line['volume'] ?? 0), 0, ',', '.') }}
                                        {{ $line['unit'] ?? '' }} × Rp {{ number_format((int) ($line['unit_price'] ?? 0), 0, ',', '.') }}
                                        = Rp {{ number_format((int) ($line['subtotal'] ?? 0), 0, ',', '.') }}
                                            @if (filled($line['funding_source'] ?? null))
                                                (Sumber: {{ $line['funding_source'] }})
                                            @endif
                                            @if (($line['ceiling'] ?? null) !== null)
                                                — Pagu Rp {{ number_format((int) $line['ceiling'], 0, ',', '.') }}
                                                @if (($line['over_ceiling'] ?? 0) > 0)
                                                    (Melebihi pagu Rp {{ number_format((int) $line['over_ceiling'], 0, ',', '.') }})
                                                @endif
                                            @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td style="text-align:right;vertical-align:top;white-space:nowrap;padding:3px;">
                        @if (in_array($type, ['income', 'expense'], true))
                            Rp {{ ($item['amount'] ?? null) === null || ($item['amount'] ?? '') === '' ? '–' : number_format((int) $item['amount'], 0, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="2" style="padding-left:18px;">–</td></tr>
            @endforelse
        </tbody>
    </table>
    <table style="width:100%;border-collapse:collapse;table-layout:fixed;margin-top:8px;font-size:9pt;page-break-inside:avoid;">
        <colgroup><col style="width:68%;"><col style="width:32%;"></colgroup>
        <tbody>
            <tr><td style="padding:3px 3px 3px 26px;">Jumlah rencana pemasukan</td><td style="text-align:right;white-space:nowrap;padding:3px;">Rp {{ number_format($financial['income'], 0, ',', '.') }}</td></tr>
            <tr><td style="padding:3px 3px 3px 26px;">Jumlah rencana pengeluaran</td><td style="text-align:right;white-space:nowrap;padding:3px;">Rp {{ number_format($financial['expense'], 0, ',', '.') }}</td></tr>
            <tr style="font-weight:bold;"><td style="padding:4px 3px 4px 26px;">Kekurangan rencana biaya / usaha dana</td><td style="text-align:right;white-space:nowrap;padding:4px 3px;">Rp {{ number_format($financial['shortfall'], 0, ',', '.') }}</td></tr>
            @if ($financial['surplus'] > 0)
                <tr><td style="padding:3px 3px 3px 26px;">Surplus rencana</td><td style="text-align:right;white-space:nowrap;padding:3px;">Rp {{ number_format($financial['surplus'], 0, ',', '.') }}</td></tr>
            @endif
        </tbody>
    </table>
</div>
