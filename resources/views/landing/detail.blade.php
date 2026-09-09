<!DOCTYPE html>
<html lang="id">
<head>@include('partials.head', ['title' => $title.' — '.$school->name])</head>
<body class="simpram-app min-h-screen bg-[#f7f5ee] text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <x-public-header :school="$school" />
    <main class="mx-auto max-w-5xl px-5 py-10 lg:py-16">
        <a href="{{ route('schools.landing', $school) }}#{{ $category === 'Dokumentasi kegiatan' ? 'dokumentasi' : 'agenda' }}" class="text-sm font-bold text-emerald-800 dark:text-emerald-300">&larr; Kembali ke {{ $school->name }}</a>
        <header class="app-page-heading mt-6">
            <p class="text-sm font-bold uppercase tracking-widest">{{ $category }}</p>
            <h1 class="mt-3 text-3xl sm:text-5xl">{{ $title }}</h1>
            @if ($date)<p class="mt-5 text-sm">{{ $date->translatedFormat('d F Y') }}</p>@endif
        </header>
        <article class="mt-6 space-y-8 rounded-3xl border border-zinc-200 bg-white p-6 sm:p-10 dark:border-zinc-800 dark:bg-zinc-900">
            @if ($activity)
                <dl class="grid gap-4 rounded-2xl bg-zinc-50 p-5 sm:grid-cols-2 dark:bg-zinc-800">
                    <div><dt class="text-sm text-zinc-500">Lokasi</dt><dd class="mt-1 font-semibold">{{ $activity->location ?: 'Lokasi menyusul' }}</dd></div>
                    <div><dt class="text-sm text-zinc-500">Waktu kegiatan</dt><dd class="mt-1 font-semibold">{{ $activity->start_at->translatedFormat('d M Y, H.i') }}@if($activity->end_at) – {{ $activity->end_at->translatedFormat('d M Y, H.i') }}@endif</dd></div>
                </dl>
            @endif
            <div class="whitespace-pre-line break-words leading-8 text-zinc-600 dark:text-zinc-300">{{ strip_tags($body ?? '') ?: 'Deskripsi belum tersedia.' }}</div>
            @if ($category === 'Dokumentasi kegiatan')
                @if ($journal)
                    @foreach (['objective' => 'Tujuan', 'material' => 'Materi', 'activity_description' => 'Pelaksanaan kegiatan', 'result' => 'Hasil kegiatan'] as $field => $label)
                        @if ($journal->$field)
                            <section><h2 class="text-xl font-bold">{{ $label }}</h2><p class="mt-3 whitespace-pre-line break-words leading-7 text-zinc-600 dark:text-zinc-300">{{ strip_tags($journal->$field) }}</p></section>
                        @endif
                    @endforeach
                    <section><h2 class="text-xl font-bold">Galeri dokumentasi</h2><div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @forelse ($journal->attachments as $attachment)
                            @if (in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif']))
                                <figure class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700"><a href="{{ Storage::disk('public')->url($attachment->path) }}"><img src="{{ Storage::disk('public')->url($attachment->path) }}" alt="{{ $attachment->original_name }}" loading="lazy" class="aspect-video w-full object-cover"></a><figcaption class="break-words p-4 text-sm">{{ $attachment->original_name }}</figcaption></figure>
                            @else
                                <a href="{{ Storage::disk('public')->url($attachment->path) }}" class="break-words rounded-2xl border border-zinc-200 p-5 font-semibold dark:border-zinc-700" download>{{ $attachment->original_name }} &darr;</a>
                            @endif
                        @empty
                            <p class="text-zinc-500">Belum ada lampiran dokumentasi.</p>
                        @endforelse
                    </div></section>
                @else
                    <p class="rounded-2xl bg-zinc-50 p-6 text-zinc-500 dark:bg-zinc-800">Dokumentasi kegiatan belum diterbitkan.</p>
                @endif
                <flux:button :href="route('schools.activities.show', [$school, $activity->id])">Detail kegiatan</flux:button>
            @elseif ($activity)
                <flux:button variant="primary" :href="route('schools.documentation.show', [$school, $activity->id])">Lihat dokumentasi</flux:button>
            @endif
        </article>
    </main>
    @fluxScripts
</body>
</html>
