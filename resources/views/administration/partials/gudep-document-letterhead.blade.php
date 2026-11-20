{{-- Reuse the exact Persuratan / LPJ letterhead renderer.
     Data may originate from a saved immutable publication snapshot. --}}
@php
    $school = (object) [
        'name' => $letterhead['school_name'] ?? '',
        'city' => $letterhead['school_city'] ?? '',
        'address' => $letterhead['school_address'] ?? '',
        'postal_code' => $letterhead['postal_code'] ?? '',
    ];
    $scoutGroup = (object) ['secretariat_address' => $letterhead['gudep_address'] ?? null];
    $documentSetting = (object) [
        'gudep_male_number' => $letterhead['male_number'] ?? null,
        'gudep_female_number' => $letterhead['female_number'] ?? null,
    ];
    $administrationProfile = (object) [
        'letterhead_title' => $letterhead['title'] ?? null,
        'letterhead_subtitle' => $letterhead['subtitle'] ?? null,
        'letterhead_address' => $letterhead['address'] ?? null,
    ];
    $administrationType = $letterhead['type'] ?? 'mabigus';
@endphp
<style>
    .letterhead {
        border-bottom: 1.6px solid #000;
        margin-bottom: 4mm;
        min-height: 22mm;
        padding: 0 2mm 2.5mm;
        position: relative;
        text-align: center;
    }
    .letterhead-logo-left, .letterhead-logo-right {
        height: 21mm;
        max-width: 23mm;
        object-fit: contain;
        position: absolute;
        top: 0;
    }
    .letterhead-logo-left { left: 1mm; }
    .letterhead-logo-right { right: 1mm; }
    .letterhead-title {
        font-family: "Times New Roman", Times, serif;
        font-size: 13pt;
        font-weight: bold;
        line-height: 1.05;
        margin: 0 23mm;
    }
    .letterhead-address {
        font-family: "Times New Roman", Times, serif;
        font-size: 9pt;
        line-height: 1.1;
        margin: .8mm 23mm 0;
    }
</style>
@include('reports.pdf.lpj.partials.letterhead')
