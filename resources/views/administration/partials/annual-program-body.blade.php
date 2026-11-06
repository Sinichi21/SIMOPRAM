@php
    $sections = \App\Support\GudepAnnualProgram::sections();
    $financial = \App\Support\GudepAnnualProgram::totals($entries);
    $gudepLabel = trim(mb_strtoupper(($kwarcab ?? '').' '.trim(($maleNumber ?? '') . (($maleNumber ?? '') && ($femaleNumber ?? '') ? '-' : '') . ($femaleNumber ?? ''))));
    $yearLabel = str_replace('/', '-', (string) ($yearName ?? '–'));
@endphp
<div class="annual-paper" style="font-family:Arial,Helvetica,sans-serif;font-size:10pt;color:#111;line-height:1.52;">
    <div style="text-align:center;margin:0 0 22px;">
        <div style="font-weight:bold;font-size:12pt;">PROGRAM KERJA TAHUNAN</div>
        <div style="font-weight:bold;font-size:11pt;">GUGUS DEPAN {{ $gudepLabel ?: '–' }}</div>
        <div style="font-weight:bold;font-size:11pt;">TAHUN {{ $yearLabel }}</div>
    </div>

    <div style="font-weight:bold;text-align:justify;margin:15px 0 6px;">A. BIDANG KEGIATAN DAN LATIHAN PESERTA DIDIK</div>
    @foreach (['siaga', 'penggalang', 'penegak', 'pandega'] as $groupIndex => $group)
        @php $rows = \App\Support\GudepAnnualProgram::numbered($entries, $group); @endphp
        @if ($rows !== [])
            <div style="margin-left:20px;margin-top:8px;font-weight:normal;">{{ $groupIndex + 1 }}. {{ ucfirst($group) }}</div>
            @foreach ($rows as $row)
                @php $item = $row['entry']; $depth = $row['depth']; @endphp
                <div style="margin-left:{{ 42 + $depth * 23 }}px;text-align:justify;margin-top:2px;page-break-inside:avoid;">
                    <span style="display:inline-block;width:19px;vertical-align:top;">{{ $row['marker'] }}</span>
                    <span style="display:inline;">{{ $item['description'] ?? '–' }}</span>
                    @if (($item['quantity'] ?? null) !== null && ($item['quantity'] ?? '') !== '')
                        <span style="white-space:nowrap;">{{ $item['quantity'] }} {{ $item['unit'] ?? 'kali' }}</span>
                    @endif
                    @if (filled($item['detail'] ?? null))
                        <div style="margin-left:20px;text-align:justify;white-space:pre-line;">{{ $item['detail'] }}</div>
                    @endif
                </div>
            @endforeach
        @endif
    @endforeach

    @foreach (['bersama', 'dewasa', 'penghargaan', 'sarana'] as $section)
        <div style="font-weight:bold;text-align:justify;margin:17px 0 6px;">{{ $sections[$section] }}</div>
        @if ($section === 'sarana')
            <div style="margin-left:20px;text-align:justify;">Mengusahakan tersedianya:</div>
        @endif
        @php $rows = \App\Support\GudepAnnualProgram::numbered($entries, $section); @endphp
        @forelse ($rows as $row)
            @php $item = $row['entry']; @endphp
            <div style="margin-left:{{ 20 + $row['depth'] * 24 }}px;text-align:justify;margin-top:2px;page-break-inside:avoid;">
                <span style="display:inline-block;width:22px;vertical-align:top;">{{ $row['marker'] }}</span>
                {{ $item['description'] ?? '–' }}
                @if (($item['quantity'] ?? null) !== null && ($item['quantity'] ?? '') !== '')
                    {{ $item['quantity'] }} {{ $item['unit'] ?? 'kali' }}
                @endif
                @if (filled($item['detail'] ?? null))
                    <div style="margin-left:22px;white-space:pre-line;text-align:justify;">{{ $item['detail'] }}</div>
                @endif
            </div>
        @empty
            <div style="margin-left:20px;">–</div>
        @endforelse
    @endforeach

    <div style="font-weight:bold;text-align:justify;margin:17px 0 7px;">F. BIDANG KEUANGAN</div>
    @php $financeRows = \App\Support\GudepAnnualProgram::numbered($entries, 'keuangan'); @endphp
    @forelse ($financeRows as $row)
        @php $item = $row['entry']; $type = $item['finance_type'] ?? ''; @endphp
        <div style="margin-left:{{ 20 + $row['depth'] * 23 }}px;text-align:justify;page-break-inside:avoid;">
            <span style="display:inline-block;width:23px;">{{ $row['marker'] }}</span>
            <span>{{ $item['description'] ?? '–' }}</span>
            @if (in_array($type, ['income', 'expense'], true))
                <span style="float:right;white-space:nowrap;">Rp {{ ($item['amount'] ?? null) === null || ($item['amount'] ?? '') === '' ? '–' : number_format((int) $item['amount'], 0, ',', '.') }}</span>
            @endif
            @if (filled($item['detail'] ?? null))
                <div style="margin-left:23px;text-align:justify;white-space:pre-line;">{{ $item['detail'] }}</div>
            @endif
        </div>
    @empty
        <div style="margin-left:20px;">–</div>
    @endforelse
    <div style="margin:11px 0 0 43px;">
        <div style="display:flex;justify-content:space-between;"><strong>Jumlah rencana pemasukan</strong><strong>Rp {{ number_format($financial['income'], 0, ',', '.') }}</strong></div>
        <div style="display:flex;justify-content:space-between;"><strong>Jumlah rencana pengeluaran</strong><strong>Rp {{ number_format($financial['expense'], 0, ',', '.') }}</strong></div>
        <div style="display:flex;justify-content:space-between;"><strong>Kekurangan rencana biaya / usaha dana</strong><strong>Rp {{ number_format($financial['shortfall'], 0, ',', '.') }}</strong></div>
        @if ($financial['surplus'] > 0)
            <div style="display:flex;justify-content:space-between;"><strong>Surplus rencana</strong><strong>Rp {{ number_format($financial['surplus'], 0, ',', '.') }}</strong></div>
        @endif
    </div>
</div>
