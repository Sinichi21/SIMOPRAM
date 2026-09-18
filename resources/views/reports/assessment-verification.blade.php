<!DOCTYPE html>
<html lang="id">
<head>@include('partials.head', ['title' => 'Validasi rekap penilaian'])</head>
<body class="min-h-screen bg-zinc-50 p-5 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <main class="mx-auto my-10 max-w-2xl space-y-5 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <h1 class="text-2xl font-bold">Validasi rekap penilaian</h1>
        <p class="text-lg font-semibold">{{ $valid ? 'Arsip cetakan valid' : 'Arsip cetakan tidak valid atau tidak tersedia' }}</p>
        <dl class="space-y-3">
            <div><dt class="text-sm text-zinc-500">Nomor dokumen</dt><dd>NILAI-{{ $report->issued_at->format('Ymd') }}-{{ $report->id }}</dd></div>
            <div><dt class="text-sm text-zinc-500">Kegiatan</dt><dd>{{ $report->snapshot['activity'] }} — {{ $report->snapshot['title'] }}</dd></div>
            <div><dt class="text-sm text-zinc-500">Penyelenggara</dt><dd>{{ $report->snapshot['organizer'] }}</dd></div>
            <div><dt class="text-sm text-zinc-500">Tanggal terbit</dt><dd>{{ $report->issued_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('d F Y H:i') }} WITA</dd></div>
            <div><dt class="text-sm text-zinc-500">Jenis rekap</dt><dd>{{ $report->format === 'judges' ? 'Rekap masing-masing juri' : 'Rekap lengkap' }} · {{ $report->with_signatures ? 'Dengan ruang tanda tangan' : 'Tanpa ruang tanda tangan' }}</dd></div>
            <div><dt class="text-sm text-zinc-500">Status saat diterbitkan</dt><dd>{{ $report->snapshot['is_final'] ? 'Hasil final' : 'Hasil sementara' }}</dd></div>
        </dl>
        <p class="text-sm text-zinc-500">Verifikasi ini berlaku untuk arsip pada tanggal terbit tersebut. Tanda tangan manual diperiksa pada dokumen cetak. Hasil kegiatan yang berubah setelah penerbitan tidak mengubah arsip ini.</p>
    </main>
</body>
</html>
