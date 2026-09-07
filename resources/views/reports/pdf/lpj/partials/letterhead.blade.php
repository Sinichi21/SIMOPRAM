@php
    $pramukaLogoFile = public_path('images/reports/logo-pramuka.png');
    $wosmLogoFile = public_path('images/reports/logo-wosm.png');

    $pramukaLogo = file_exists($pramukaLogoFile)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($pramukaLogoFile))
        : null;

    $wosmLogo = file_exists($wosmLogoFile)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($wosmLogoFile))
        : null;
@endphp

<header class="letterhead">
    @if ($pramukaLogo)
        <img
            class="letterhead-logo-left"
            src="{{ $pramukaLogo }}"
            alt="Logo Gerakan Pramuka"
        >
    @endif

    @if ($wosmLogo)
        <img
            class="letterhead-logo-right"
            src="{{ $wosmLogo }}"
            alt="Logo WOSM"
        >
    @endif

    <div class="letterhead-title">
        GERAKAN PRAMUKA<br>

        @if ($documentSetting?->gudep_male_number)
            GUGUSDEPAN {{ strtoupper($school->city ?: '') }}
            {{ $documentSetting->gudep_male_number }}<br>
        @endif

        @if ($documentSetting?->gudep_female_number)
            GUGUSDEPAN {{ strtoupper($school->city ?: '') }}
            {{ $documentSetting->gudep_female_number }}<br>
        @endif

        PANGKALAN {{ strtoupper($school->name) }}
    </div>

    <div class="letterhead-address">
        {{ $scoutGroup?->secretariat_address ?: $school->address }}

        @if ($school->postal_code)
            {{ $school->postal_code }}
        @endif
    </div>
</header>