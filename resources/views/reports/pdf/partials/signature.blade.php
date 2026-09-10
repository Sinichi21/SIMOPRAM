@php
    $signatoryService = app(\App\Services\DocumentSignatoryService::class);

    $principal = $signatoryService->configured($documentSetting, 'principal', (int) $school->id);
    $responsible = $signatoryService->configured($documentSetting, 'responsible', (int) $school->id);

    $legacyCoach = $documentSetting?->responsibleCoach;

    $principalName = $principal['name'] ?? $documentSetting?->principal_name;
    $principalPosition = $principal['position'] ?? 'Kepala Sekolah';
    $principalIdentity = $principal['identity']
        ?? ($documentSetting?->principal_nip ? 'NIP. '.$documentSetting->principal_nip : '');

    $responsibleName = $responsible['name'] ?? $legacyCoach?->name;
    $responsiblePosition = $responsible['position'] ?? 'Pembina Pramuka';
    $responsibleIdentity = $responsible['identity']
        ?? ($legacyCoach?->nip ? 'NTA. '.$legacyCoach->nip : '');

    $signingCity = $documentSetting?->signing_city ?: $school->city ?: '................';
@endphp

<table class="signature">
    <tr>
        <td>
            Mengetahui,<br>
            {{ $principalPosition }}
            <div class="signature-space"></div>

            @if ($principalName)
                <strong>{{ $principalName }}</strong>
            @else
                ______________________________
            @endif

            <br>
            {{ $principalIdentity ?: '________________________' }}
        </td>

        <td>
            {{ $signingCity }}, {{ now()->locale('id')->translatedFormat('d F Y') }}<br>
            {{ $responsiblePosition }}
            <div class="signature-space"></div>

            @if ($responsibleName)
                <strong>{{ $responsibleName }}</strong>
            @else
                ______________________________
            @endif

            <br>
            {{ $responsibleIdentity ?: '________________________' }}
        </td>
    </tr>
</table>
