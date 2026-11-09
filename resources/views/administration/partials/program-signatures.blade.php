@php
    $programSignatures = $programSignatures ?? [];
    $ack = $programSignatures['acknowledger'] ?? null;
    $sign = $programSignatures['signer'] ?? null;
    $city = trim((string) ($programCity ?? ''));
    $number = trim((string) ($programNumber ?? ''));
    $identityLeft = trim((string) ($ack['identity'] ?? ''));
    $identityRight = trim((string) ($sign['identity'] ?? ''));
    $signPosition = mb_strtolower(trim((string) ($sign['position'] ?? '')));
    $rightTitle = str_contains($signPosition, 'pembina') && ! str_contains($signPosition, 'ketua')
        ? 'Pembina,' : 'Ketua,';
@endphp
{{-- Keep both names, roles, optional NTA/NIP and dates together on one page.
     The wrapper and table row both prevent page fragmentation in Dompdf. --}}
<div style="page-break-inside:avoid;break-inside:avoid;width:100%;margin-top:20px;font-family:'Times New Roman',Times,serif;font-size:10pt;">
    <table style="width:100%;table-layout:fixed;border-collapse:collapse;page-break-inside:avoid;" cellpadding="2">
        <tbody>
            <tr style="page-break-inside:avoid;">
                <td style="width:50%;vertical-align:top;border:0;padding-right:15px;">
                    <div>Mengetahui,</div>
                    <div>Majelis Pembimbing Gugusdepan</div>
                    <div>Ketua,</div>
                    <div style="height:58px;"></div>
                    <div><strong><u>{{ $ack['name'] ?? '(Nama belum ditentukan)' }}</u></strong></div>
                    @if ($identityLeft !== '')
                        <div style="font-size:8pt;">{{ $identityLeft }}</div>
                    @endif
                </td>
                <td style="width:50%;vertical-align:top;border:0;padding-left:15px;">
                    <div>{{ $city !== '' ? $city : '................' }}, {{ $programDate ?? '........................' }}</div>
                    <div>Gugusdepan {{ trim($city.' '.$number) ?: '................' }}</div>
                    <div>{{ $rightTitle }}</div>
                    <div style="height:58px;"></div>
                    <div><strong><u>{{ $sign['name'] ?? '(Nama belum ditentukan)' }}</u></strong></div>
                    @if ($identityRight !== '')
                        <div style="font-size:8pt;">{{ $identityRight }}</div>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</div>
