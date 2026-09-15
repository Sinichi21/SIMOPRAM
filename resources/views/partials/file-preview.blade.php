<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pratinjau berkas</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="bg-zinc-100 p-8 text-zinc-900">
    <main class="mx-auto max-w-2xl space-y-5 rounded-xl bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-semibold">Pratinjau berkas</h1>
        <p class="break-words font-medium">{{ $filename }}</p>
        @if($previewText !== null)
            <p>Pratinjau teks tanpa tata letak asli. Excel menampilkan lembar pertama, maksimal 200 baris dan 52 kolom. Unduh untuk melihat dokumen lengkap.</p>
            <pre class="max-h-[65vh] overflow-auto whitespace-pre-wrap rounded-lg bg-zinc-50 p-4 text-sm">{{ $previewText }}</pre>
        @else
            <p>Pratinjau isi belum tersedia untuk berkas ini. Unduh berkas untuk melihat isinya di aplikasi yang sesuai.</p>
        @endif
        <a href="{{ $downloadUrl }}" class="inline-block rounded-lg bg-zinc-900 px-4 py-2 text-white">Unduh berkas</a>
    </main>
</body>
</html>
