@php
    $principalName =
        $documentSetting?->principal_name;

    $principalNip =
        $documentSetting?->principal_nip;

    $coach =
        $documentSetting?->responsibleCoach;

    $signingCity =
        $documentSetting?->signing_city
        ?: $school->city
        ?: '................';
@endphp


<table class="signature">

    <tr>

        <td>
            Mengetahui,
            <br>

            Kepala Sekolah

            <div class="signature-space"></div>


            @if ($principalName)

                <strong>
                    {{ $principalName }}
                </strong>

            @else

                ______________________________

            @endif


            <br>


            @if ($principalNip)

                NIP. {{ $principalNip }}

            @else

                NIP. ________________________

            @endif
        </td>


        <td>
            {{ $signingCity }},
            {{ now()->translatedFormat(
                'd F Y'
            ) }}

            <br>

            Pembina Pramuka

            <div class="signature-space"></div>


            @if ($coach)

                <strong>
                    {{ $coach->name }}
                </strong>

            @else

                ______________________________

            @endif


            <br>


            @if ($coach?->nip)

                NTA. {{ $coach->nip }}

            @else

                NTA. ________________________

            @endif
        </td>

    </tr>

</table>