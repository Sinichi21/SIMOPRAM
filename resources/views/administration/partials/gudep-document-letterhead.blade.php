@php
    $pramukaFile = public_path('images/reports/logo-pramuka.png');
    $wosmFile = public_path('images/reports/logo-wosm.png');
    $pramukaImage = is_file($pramukaFile) ? 'data:image/png;base64,'.base64_encode(file_get_contents($pramukaFile)) : null;
    $wosmImage = is_file($wosmFile) ? 'data:image/png;base64,'.base64_encode(file_get_contents($wosmFile)) : null;
@endphp
<header style="position:relative;min-height:82px;border-bottom:2px solid #171717;padding:6px 75px 10px;text-align:center;margin-bottom:18px;font-family:'Times New Roman',Times,serif;color:#111;">
    @if ($pramukaImage)
        <img src="{{ $pramukaImage }}" alt="Logo Pramuka" style="position:absolute;top:4px;left:4px;max-width:66px;max-height:65px;">
    @endif
    @if ($wosmImage)
        <img src="{{ $wosmImage }}" alt="Logo WOSM" style="position:absolute;top:4px;right:4px;max-width:66px;max-height:65px;">
    @endif
    <div style="font-weight:bold;font-size:14pt;line-height:1.17;">{{ $letterhead['title'] }}</div>
    <div style="font-weight:bold;font-size:12pt;line-height:1.17;">{{ $letterhead['subtitle'] }}</div>
    <div style="font-size:11pt;line-height:1.17;">PANGKALAN {{ mb_strtoupper($letterhead['school']) }}</div>
    <div style="font-size:9pt;line-height:1.25;margin-top:5px;">{{ $letterhead['address'] ?: 'Alamat sekretariat belum diatur' }}</div>
</header>
