<section id="agenda" class="px-5 py-16 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-10">
        <div><p class="text-sm font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Informasi Umum</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">Pengumuman &amp; Kegiatan</h2><p class="mt-3 text-zinc-500">Informasi, agenda, dan lomba terbuka dari pengelola {{ config('app.name') }}.</p></div>
        <div class="space-y-5"><h3 class="text-xl font-bold">Pengumuman</h3><div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($announcements as $announcement)
                <article class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-xs text-zinc-500">{{ $announcement->published_at?->translatedFormat('d F Y') }}</p><h4 class="mt-3 text-xl font-bold break-words">{{ $announcement->title }}</h4><p class="mt-3 grow text-sm leading-6 text-zinc-500">{{ Str::limit(strip_tags($announcement->body), 160) }}</p><a href="{{ route('public.announcements.show', $announcement->id) }}" class="mt-5 font-semibold text-emerald-800 dark:text-emerald-300">Detail pengumuman &rarr;</a></article>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500">Belum ada pengumuman umum.</p>
            @endforelse
        </div></div>
        <div class="space-y-5"><h3 class="text-xl font-bold">Kegiatan &amp; Lomba</h3><div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($activities as $activity)
                <article class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-xs text-zinc-500">{{ $activity->start_at->translatedFormat('d F Y, H.i') }}</p><h4 class="mt-3 text-xl font-bold break-words">{{ $activity->title }}</h4><p class="mt-2 text-sm text-zinc-500">{{ $activity->location ?: 'Lokasi menyusul' }}</p><p class="mt-3 grow text-sm leading-6 text-zinc-500">{{ Str::limit(strip_tags($activity->description ?? ''), 160) }}</p><a href="{{ route('public.activities.show', $activity->id) }}" class="mt-5 font-semibold text-emerald-800 dark:text-emerald-300">Detail kegiatan &rarr;</a></article>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500">Belum ada kegiatan umum.</p>
            @endforelse
        </div></div>
    </div>
</section>
