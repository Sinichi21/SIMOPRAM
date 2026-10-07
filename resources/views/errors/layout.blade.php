@php
    $status = (int) ($code ?? $exception->getStatusCode());
    [$heading, $description, $hint] = match ($status) {
        401 => ['Silakan masuk terlebih dahulu.', 'Halaman ini hanya tersedia untuk pengguna yang sudah masuk.', 'Masuk dengan akun yang terdaftar untuk melanjutkan.'],
        403 => ['Akses belum tersedia.', 'Akun Anda belum memiliki izin untuk membuka halaman ini.', 'Hubungi pengelola sekolah jika Anda memerlukan akses.'],
        404 => ['Sepertinya salah arah.', 'Halaman yang Anda cari tidak ditemukan. Alamatnya mungkin berubah atau halaman sudah tidak tersedia.', 'Periksa kembali alamat halaman atau mulai dari beranda.'],
        419 => ['Sesi Anda telah berakhir.', 'Halaman ini sudah terlalu lama terbuka. Silakan buka kembali halaman dan coba lagi.', 'Jika sebelumnya mengisi formulir, periksa kembali data sebelum mengirim ulang.'],
        429 => ['Istirahat sejenak, yuk.', 'Terlalu banyak permintaan dalam waktu singkat.', 'Tunggu beberapa saat sebelum membuka halaman kembali.'],
        500 => ['Ada kendala di sistem.', 'Kami belum dapat memproses permintaan Anda saat ini.', 'Silakan coba lagi nanti. Jika kendala berlanjut, hubungi pengelola.'],
        502, 504 => ['Koneksi sedang terkendala.', 'Layanan belum dapat merespons permintaan Anda saat ini.', 'Tunggu beberapa saat, lalu buka kembali halaman ini.'],
        503 => ['Kami akan segera kembali.', 'SIMPRAM sedang dalam pemeliharaan atau sementara belum tersedia.', 'Silakan kunjungi kembali beberapa saat lagi. Terima kasih atas kesabaran Anda.'],
        default => $status >= 500
            ? ['Layanan sedang terkendala.', 'Permintaan Anda belum dapat diproses saat ini.', 'Silakan coba kembali beberapa saat lagi.']
            : ['Halaman belum dapat dibuka.', 'Permintaan Anda tidak dapat diproses.', 'Periksa kembali alamat halaman atau kembali ke beranda.'],
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $status }} — {{ $heading }} | SIMPRAM</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    {{-- Self-contained styles keep error pages available when Vite assets cannot be loaded. --}}
    <style>
        :root { color-scheme: light; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; color: #022c22; background: #f7f5ee; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100svh; display: flex; flex-direction: column; -webkit-font-smoothing: antialiased; }
        a { color: inherit; text-decoration: none; }
        a:focus-visible { outline: 3px solid #047857; outline-offset: 6px; }
        .container { width: min(100% - 40px, 1216px); margin-inline: auto; }
        header { border-bottom: 1px solid #022c221a; }
        .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 24px; padding-block: 18px; }
        .brand { display: inline-flex; align-items: center; gap: 12px; }
        .brand-mark { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 12px; background: #065f46; color: #fcd34d; font-size: 20px; font-weight: 900; }
        .brand strong { display: block; letter-spacing: -.025em; }
        .brand small { display: block; margin-top: 3px; font-size: 10px; font-weight: 700; letter-spacing: .18em; color: #065f46; }
        .header-link { font-size: 14px; font-weight: 700; }
        .header-link:hover { color: #047857; }
        main { flex: 1; display: grid; align-items: center; overflow: hidden; background: radial-gradient(ellipse at 90% 25%, #fcd34d35, transparent 45%), radial-gradient(ellipse at 0% 95%, #10b98120, transparent 45%); }
        .hero { display: grid; grid-template-columns: 1.1fr .9fr; align-items: center; gap: 72px; padding-block: 88px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 10px; border: 1px solid #065f4626; border-radius: 999px; padding: 10px 16px; background: #ffffffb3; color: #065f46; font-size: 11px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: #d97706; flex-shrink: 0; }
        h1 { margin: 26px 0 0; max-width: 640px; font-size: clamp(40px, 5.6vw, 72px); line-height: 1.02; letter-spacing: -.045em; font-weight: 900; text-wrap: balance; }
        .description { margin: 24px 0 0; max-width: 520px; color: #475569; font-size: 18px; line-height: 1.8; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 14px 24px; border-radius: 999px; font-size: 14px; font-weight: 750; background: #065f46; color: white; transition: background .15s; }
        .button:hover { background: #047857; }
        .button-secondary { background: #fcd34d; color: #022c22; }
        .button-secondary:hover { background: #fde68a; }
        .card { padding: 22px; border-radius: 32px; background: #022c22; box-shadow: 0 24px 60px #022c2226; transform: rotate(2deg); }
        .card-inner { padding: 32px; border-radius: 22px; background: #065f46; color: white; }
        .card-top { display: flex; align-items: center; justify-content: space-between; gap: 16px; color: #a7f3d0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .18em; }
        .compass { width: 44px; height: 44px; padding: 10px; border-radius: 14px; background: #fcd34d; color: #022c22; flex-shrink: 0; }
        .status { margin: 24px 0; color: #fcd34d; font-size: clamp(100px, 13vw, 164px); line-height: 1; font-weight: 900; letter-spacing: -.065em; }
        .hint { padding: 22px; border-radius: 16px; background: #fff; color: #475569; }
        .hint strong { display: block; margin-bottom: 10px; color: #065f46; font-size: 11px; letter-spacing: .15em; text-transform: uppercase; }
        .hint p { margin: 0; font-size: 14px; line-height: 1.75; }
        footer { border-top: 1px solid #e2e8f0; background: white; color: #64748b; font-size: 13px; }
        .footer-inner { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-block: 24px; }
        .footer-inner p { margin: 0; }
        @media (max-width: 800px) {
            .hero { grid-template-columns: 1fr; gap: 44px; padding-block: 48px; }
            .card { width: min(100% - 12px, 440px); margin-inline: auto; }
            .card-inner { padding: 24px; }
            .status { font-size: 112px; }
        }
        @media (max-width: 420px) {
            .header-link { font-size: 12px; }
            .actions { flex-direction: column; }
            .card { padding: 16px; }
            .card-inner { padding: 20px; }
        }
        @media (prefers-reduced-motion: reduce) { .button { transition: none; } }
    </style>
</head>
<body>
    <header>
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="brand" aria-label="SIMPRAM Beranda">
                <span class="brand-mark">S</span>
                <span><strong>SIMPRAM</strong><small>PRAMUKA DIGITAL</small></span>
            </a>
            <a href="{{ route('home') }}" class="header-link">Beranda <span aria-hidden="true">↗</span></a>
        </div>
    </header>
    <main>
        <div class="container hero">
            <section aria-labelledby="error-heading">
                <div class="eyebrow"><span class="dot" aria-hidden="true"></span> {{ $status >= 500 ? 'Layanan sementara terganggu' : 'Ada kendala dalam perjalanan' }}</div>
                <h1 id="error-heading">{{ $heading }}</h1>
                <p class="description">{{ $description }}</p>
                <div class="actions">
                    <a href="{{ route('home') }}" class="button">Kembali ke beranda <span aria-hidden="true">&nbsp;→</span></a>
                    @if ($status === 401 || $status === 419)
                        <a href="{{ route('login') }}" class="button button-secondary">Masuk kembali</a>
                    @else
                        <a href="mailto:admin@simpram.my.id" class="button button-secondary">Hubungi pengelola</a>
                    @endif
                </div>
            </section>
            <aside class="card" aria-label="Informasi kesalahan">
                <div class="card-inner">
                    <div class="card-top">
                        <span>Kode kesalahan</span>
                        <svg class="compass" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m16 8-2.5 5.5L8 16l2.5-5.5L16 8Z"/></svg>
                    </div>
                    <p class="status">{{ $status }}</p>
                    <div class="hint"><strong>Langkah berikutnya</strong><p>{{ $hint }}</p></div>
                </div>
            </aside>
        </div>
    </main>
    <footer>
        <div class="container footer-inner">
            <p>© {{ date('Y') }} SIMPRAM. Bersama membina generasi.</p>
            <p>Pramuka tertata. Karakter terbina.</p>
        </div>
    </footer>
</body>
</html>
