@php
    $programSignatures = $programSignatures ?? [];
    $ack = $programSignatures['acknowledger'] ?? null;
    $sign = $programSignatures['signer'] ?? null;
@endphp
<table style="width:100%;margin-top:24px;border:0;border-collapse:collapse;page-break-inside:avoid;font-family:'Times New Roman',Times,serif;font-size:10pt;" cellpadding="3">
    <tr>
        <td style="width:50%;border:0;vertical-align:top;text-align:left;">
            Mengetahui,<div>{{ $programSignatures['acknowledger_role'] ?? 'Ketua Majelis Pembimbing Gugusdepan' }},</div>
            <div style="height:55px;"></div>
            <strong><u>{{ $ack['name'] ?? '(Nama belum ditentukan)' }}</u></strong>
            <div style="font-size:8pt;">{{ $ack['position'] ?? '–' }}</div>
        </td>
        <td style="width:50%;border:0;vertical-align:top;text-align:left;">
            {{ $programDate ?? '...........................' }}
            <div>{{ $programSignatures['signer_role'] ?? 'Pembina Gugusdepan' }},</div>
            <div style="height:55px;"></div>
            <strong><u>{{ $sign['name'] ?? '(Nama belum ditentukan)' }}</u></strong>
            <div style="font-size:8pt;">{{ $sign['position'] ?? '–' }}</div>
        </td>
    </tr>
</table>
