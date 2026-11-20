@php
    $sections = \App\Support\GudepAnnualProgram::sections();
    $financial = \App\Support\GudepAnnualProgram::totals($entries);
    $gudepLabel = trim(mb_strtoupper(($kwarcab ?? '').' '.($programNumber ?? trim(($maleNumber ?? '').(($maleNumber ?? '') && ($femaleNumber ?? '') ? '-' : '').($femaleNumber ?? '')))));
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

    @include('administration.partials.annual-program-finance', ['entries' => $entries])
</div>
