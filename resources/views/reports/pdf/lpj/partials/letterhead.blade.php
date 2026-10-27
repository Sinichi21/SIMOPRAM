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

    $parentInstitution = trim((string) ($adminProfile?->letterhead_title ?: 'GERAKAN PRAMUKA'));
    $baseName = trim((string) ($adminProfile?->letterhead_subtitle ?: $school->name));
@endphp

<header class="letterhead">
    @if ($pramukaLogo)
        <img class="letterhead-logo-left" src="{{ $pramukaLogo }}" alt="Logo Gerakan Pramuka">
    @endif

    @if ($wosmLogo)
        <img class="letterhead-logo-right" src="{{ $wosmLogo }}" alt="Logo WOSM">
    @endif

    <div class="letterhead-title">
        <span class="letterhead-institution">{{ $parentInstitution }}</span><br>

        @if (in_array($adminType, ['male', 'mabigus'], true) && filled($documentSetting?->gudep_male_number))
            <span class="letterhead-gudep">GUGUSDEPAN {{ strtoupper($school->city ?: '') }} {{ $documentSetting->gudep_male_number }}</span><br>
        @endif
        @if (in_array($adminType, ['female', 'mabigus'], true) && filled($documentSetting?->gudep_female_number))
            <span class="letterhead-gudep">GUGUSDEPAN {{ strtoupper($school->city ?: '') }} {{ $documentSetting->gudep_female_number }}</span><br>
        @endif

        <span class="letterhead-base">PANGKALAN {{ strtoupper($baseName) }}</span>
    </div>

    <div class="letterhead-address">{{ $letterheadAddress }}</div>
</header>
