@php
    $pramukaLogoFile = public_path('images/reports/logo-pramuka.png');
    $wosmLogoFile = public_path('images/reports/logo-wosm.png');

    $pramukaLogo = file_exists($pramukaLogoFile)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($pramukaLogoFile))
        : null;

    $wosmLogo = file_exists($wosmLogoFile)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($wosmLogoFile))
        : null;

    // Variabel administrationProfile/administrationType hanya dikirim oleh Persuratan.
    // LPJ lama tetap memakai perilaku gabungan Putra + Putri.
    $adminProfile = $administrationProfile ?? null;
    $adminType = $administrationType ?? 'mabigus';

    $letterheadAddress = trim((string) ($adminProfile?->letterhead_address ?: ($scoutGroup?->secretariat_address ?: $school->address)));
    $postalCode = trim((string) $school->postal_code);

    if ($postalCode !== '' && ! preg_match('/(?:^|\D)'.preg_quote($postalCode, '/').'(?:\D|$)/u', $letterheadAddress)) {
        $letterheadAddress = trim($letterheadAddress.' '.$postalCode);
    }

    $customTitle = trim((string) ($adminProfile?->letterhead_title ?? ''));
    $customSubtitle = trim((string) ($adminProfile?->letterhead_subtitle ?? ''));
@endphp

<header class="letterhead">
    @if ($pramukaLogo)
        <img class="letterhead-logo-left" src="{{ $pramukaLogo }}" alt="Logo Gerakan Pramuka">
    @endif

    @if ($wosmLogo)
        <img class="letterhead-logo-right" src="{{ $wosmLogo }}" alt="Logo WOSM">
    @endif

    <div class="letterhead-title">
        {{ $customTitle !== '' ? $customTitle : 'GERAKAN PRAMUKA' }}<br>

        @if ($customSubtitle !== '')
            {{ $customSubtitle }}<br>
        @else
            @if ($adminType === 'male')
                @if ($documentSetting?->gudep_male_number)
                    GUGUSDEPAN {{ strtoupper($school->city ?: '') }} {{ $documentSetting->gudep_male_number }}<br>
                @endif
            @elseif ($adminType === 'female')
                @if ($documentSetting?->gudep_female_number)
                    GUGUSDEPAN {{ strtoupper($school->city ?: '') }} {{ $documentSetting->gudep_female_number }}<br>
                @endif
            @else
                @if ($documentSetting?->gudep_male_number)
                    GUGUSDEPAN {{ strtoupper($school->city ?: '') }} {{ $documentSetting->gudep_male_number }}<br>
                @endif
                @if ($documentSetting?->gudep_female_number)
                    GUGUSDEPAN {{ strtoupper($school->city ?: '') }} {{ $documentSetting->gudep_female_number }}<br>
                @endif
            @endif

            PANGKALAN {{ strtoupper($school->name) }}
        @endif
    </div>

    <div class="letterhead-address">{{ $letterheadAddress }}</div>
</header>
