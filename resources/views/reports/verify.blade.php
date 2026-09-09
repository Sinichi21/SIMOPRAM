<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Dokumen SIMPRAM</title>
    <style>
        *{box-sizing:border-box} body{margin:0;background:#f4f4f5;color:#18181b;font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px 16px}.card{width:100%;max-width:760px;overflow:hidden;border:1px solid #e4e4e7;border-radius:20px;background:#fff;box-shadow:0 20px 45px rgba(24,24,27,.08)}.header,.content{padding:28px}.header{border-bottom:1px solid #e4e4e7}.eyebrow,.label{color:#71717a;font-size:12px}.eyebrow{font-weight:700;letter-spacing:.08em;text-transform:uppercase}h1{margin:8px 0 0;font-size:26px}.subtitle,.privacy{color:#71717a;line-height:1.6}.status{margin-bottom:24px;padding:16px 18px;border-radius:14px;border:1px solid}.status strong{display:block;margin-bottom:4px}.valid{border-color:#bbf7d0;background:#f0fdf4;color:#166534}.superseded{border-color:#fde68a;background:#fffbeb;color:#92400e}.revoked{border-color:#fecaca;background:#fef2f2;color:#991b1b}.grid{display:grid;grid-template-columns:1fr 1fr;gap:1px;border:1px solid #e4e4e7;border-radius:14px;overflow:hidden;background:#e4e4e7}.item{padding:16px;background:#fff}.value{margin-top:6px;font-weight:650;word-break:break-word}.checksum-wrap{margin-top:18px;padding:16px;border-radius:14px;background:#f4f4f5}.checksum{margin-top:6px;font-family:monospace;font-size:12px;word-break:break-all}.footer{padding:18px 28px;border-top:1px solid #e4e4e7;color:#71717a;font-size:12px}@media(max-width:640px){.grid{grid-template-columns:1fr}.header,.content{padding:22px}}
    </style>
</head>
<body>
@php
    $closure = $verification->closure;
    $school = $verification->school;
@endphp
<div class="page"><main class="card">
    <header class="header"><div class="eyebrow">SIMPRAM</div><h1>Verifikasi Dokumen</h1><p class="subtitle">Memeriksa identitas penerbitan dan integritas dokumen resmi SIMPRAM tanpa menampilkan isi privat dokumen.</p></header>
    <section class="content">
        @if ($status === 'valid')
            <div class="status valid"><strong>Dokumen Valid</strong>Kode verifikasi terdaftar dan dokumen masih berstatus resmi.</div>
        @elseif ($status === 'superseded')
            <div class="status superseded"><strong>Dokumen Versi Lama</strong>Dokumen pernah diterbitkan resmi, tetapi sumber snapshot kemudian diperbarui.</div>
        @else
            <div class="status revoked"><strong>Dokumen Dicabut</strong>Dokumen ini telah dicabut dari daftar dokumen resmi.@if($verification->revocation_reason) Alasan: {{ $verification->revocation_reason }} @endif</div>
        @endif
        <div class="grid">
            <div class="item"><div class="label">Sekolah</div><div class="value">{{ $school?->name ?? '-' }}</div></div>
            <div class="item"><div class="label">Jenis Dokumen</div><div class="value">{{ $verification->documentTypeLabel() }}</div></div>
            <div class="item"><div class="label">Judul</div><div class="value">{{ $verification->publicTitle() }}</div></div>
            <div class="item"><div class="label">Nomor Dokumen</div><div class="value">{{ $verification->document_number ?: '-' }}</div></div>
            @if ($closure)
                <div class="item"><div class="label">Tahun Ajaran</div><div class="value">{{ $closure->academicYear?->name ?? '-' }}</div></div>
                <div class="item"><div class="label">Semester</div><div class="value">{{ $closure->semester?->name ?? '-' }}</div></div>
                <div class="item"><div class="label">Versi Snapshot</div><div class="value">v{{ $closure->version ?? '-' }}</div></div>
            @endif
            <div class="item"><div class="label">Diterbitkan</div><div class="value">{{ $verification->issued_at?->format('d/m/Y H:i:s') ?? '-' }}</div></div>
            <div class="item"><div class="label">Kode Verifikasi</div><div class="value">{{ $verification->code }}</div></div>
        </div>
        <div class="checksum-wrap"><div class="label">Snapshot Checksum SHA-256</div><div class="checksum">{{ $verification->snapshot_checksum ?? '-' }}</div></div>
        <p class="privacy">Halaman publik tidak menampilkan isi surat, data siswa, nilai individu, atau informasi privat lainnya. Untuk dokumen berklasifikasi Terbatas/Rahasia, judul asli juga disembunyikan.</p>
    </section>
    <footer class="footer">SIMPRAM · Sistem Informasi Pramuka</footer>
</main></div>
</body>
</html>
