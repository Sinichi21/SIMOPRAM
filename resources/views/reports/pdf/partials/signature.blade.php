@php
    $signatoryService = app(\App\Services\DocumentSignatoryService::class);

    $principal = $documentSetting?->principal_signatory_user_id
        ? $signatoryService->resolve((int) $documentSetting->principal_signatory_user_id, (int) $school->id)
        : null;
    $responsible = $documentSetting?->responsible_signatory_user_id
        ? $signatoryService->resolve((int) $documentSetting->responsible_signatory_user_id, (int) $school->id)
        : null;

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
