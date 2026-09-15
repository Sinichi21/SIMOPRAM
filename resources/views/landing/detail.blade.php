<!DOCTYPE html>
<html lang="id">
<head>@include('partials.head', ['title' => $title.' — '.($school?->name ?? config('app.name'))])</head>
<body class="simpram-app min-h-screen bg-[#f7f5ee] text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <x-public-header :school="$school" />
    <main class="mx-auto max-w-5xl px-5 py-10 lg:py-16">
        <a href="{{ $school ? route('schools.landing', $school) : route('home') }}#{{ $category === 'Dokumentasi kegiatan' ? 'dokumentasi' : 'agenda' }}" class="text-sm font-bold text-emerald-800 dark:text-emerald-300">&larr; Kembali ke {{ $school?->name ?? 'halaman utama' }}</a>
        <header class="app-page-heading mt-6">
            @if($parentActivity ?? null)<a class="font-semibold underline" href="{{ $school ? route('schools.activities.show', [$school, $parentActivity->id]) : route('public.activities.show', $parentActivity->id) }}">Agenda induk: {{ $parentActivity->title }}</a>@endif
            <p class="text-sm font-bold uppercase tracking-widest">{{ $category }}</p>
            <h1 class="mt-3 text-3xl sm:text-5xl">{{ $title }}</h1>
            @if ($date)<p class="mt-5 text-sm">{{ $date->translatedFormat('d F Y') }}</p>@endif
        </header>
        <article class="mt-6 space-y-8 rounded-3xl border border-zinc-200 bg-white p-6 sm:p-10 dark:border-zinc-800 dark:bg-zinc-900">
            @if ($activity)
                @if($activity->banner_path)<img src="{{ Storage::disk('public')->url($activity->banner_path) }}" alt="Banner {{ $activity->title }}" class="max-h-96 w-full rounded-2xl object-contain" />@endif
                @if(! $activity->school_id)<nav class="flex flex-wrap gap-3" aria-label="Menu kegiatan"><flux:button :href="route('public.activities.participants', $activity->id)">Peserta</flux:button>@if($activity->registration_open && in_array($activity->status, ['published','ongoing']) && $activity->end_at?->gt(now()))<flux:button variant="primary" :href="route('public.activities.register', $activity->id)">Registrasi peserta</flux:button>@endif</nav>@endif
                <dl class="grid gap-4 rounded-2xl bg-zinc-50 p-5 sm:grid-cols-2 dark:bg-zinc-800">
                    <div><dt class="text-sm text-zinc-500">Penyelenggara</dt><dd class="mt-1 font-semibold">{{ $school?->name ?? config('app.name') }}</dd></div>
                    <div><dt class="text-sm text-zinc-500">Status</dt><dd class="mt-1 font-semibold">{{ ['published' => 'Terjadwal', 'ongoing' => 'Berlangsung', 'completed' => 'Selesai'][$activity->status] ?? $activity->status }}</dd></div>
                    <div><dt class="text-sm text-zinc-500">Jenis kegiatan</dt><dd class="mt-1 font-semibold">{{ ['regular' => 'Latihan rutin', 'training' => 'Pelatihan', 'ceremony' => 'Upacara', 'camp' => 'Perkemahan', 'competition' => 'Lomba', 'service' => 'Bakti sosial', 'other' => 'Lainnya'][$activity->activity_type] ?? $activity->activity_type }}</dd></div>
                    <div><dt class="text-sm text-zinc-500">Sasaran peserta</dt><dd class="mt-1 font-semibold">{{ $activity->scoutLevels->pluck('name')->implode(', ') ?: 'Umum' }}</dd></div>
                    @if ($activity->coaches->isNotEmpty())<div><dt class="text-sm text-zinc-500">Pembina / penanggung jawab</dt><dd class="mt-1 font-semibold">{{ $activity->coaches->pluck('name')->implode(', ') }}</dd></div>@endif
                    <div><dt class="text-sm text-zinc-500">Lokasi</dt><dd class="mt-1 font-semibold">{{ $activity->location ?: 'Lokasi menyusul' }}</dd></div>
                    <div><dt class="text-sm text-zinc-500">Waktu kegiatan</dt><dd class="mt-1 font-semibold">{{ $activity->start_at->translatedFormat('d M Y, H.i') }}@if($activity->end_at) – {{ $activity->end_at->translatedFormat('d M Y, H.i') }}@endif</dd></div>
                </dl>
            @endif
            @if ($announcement ?? null)
                <dl class="grid gap-4 rounded-2xl bg-zinc-50 p-5 sm:grid-cols-2 dark:bg-zinc-800">
                    <div><dt class="text-sm text-zinc-500">Penerbit</dt><dd class="mt-1 font-semibold">{{ $school?->name ?? config('app.name') }}</dd></div>
                    @if ($announcement->creator)<div><dt class="text-sm text-zinc-500">Diterbitkan oleh</dt><dd class="mt-1 font-semibold">{{ $announcement->creator->name }}</dd></div>@endif
                    @if ($announcement->expires_at)<div><dt class="text-sm text-zinc-500">Berlaku sampai</dt><dd class="mt-1 font-semibold">{{ $announcement->expires_at->translatedFormat('d F Y, H.i') }} ({{ config('app.timezone') }})</dd></div>@endif
                    <div><dt class="text-sm text-zinc-500">Sasaran informasi</dt><dd class="mt-1 font-semibold">Masyarakat umum</dd></div>
                </dl>
            @endif
            <div class="whitespace-pre-line break-words leading-8 text-zinc-600 dark:text-zinc-300">{{ strip_tags($body ?? '') ?: 'Deskripsi belum tersedia.' }}</div>
            @php($contentAttachments = $activity?->attachments ?? ($announcement ?? null)?->attachments ?? [])
            @if($contentAttachments)<section class="space-y-3"><h2 class="text-xl font-bold">Lampiran</h2>@foreach($contentAttachments as $index => $file)<flux:button :href="route('public.content.attachment', [$activity ? 'activities' : 'announcements', $activity?->id ?? $announcement->id, $index])" target="_blank" rel="noopener noreferrer">Lihat {{ $file['name'] }}</flux:button>@endforeach</section>@endif
            @if ($activity?->location)
                <flux:button :href="'https://www.google.com/maps/search/?api=1&query='.rawurlencode($activity->location)" target="_blank" rel="noopener noreferrer">Lihat lokasi</flux:button>
            @endif
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
                                <figure class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700"><a href="{{ Storage::disk('public')->url($attachment->path) }}" target="_blank" rel="noopener noreferrer"><img src="{{ Storage::disk('public')->url($attachment->path) }}" alt="{{ $attachment->original_name }}" loading="lazy" class="aspect-video w-full object-cover"></a><figcaption class="break-words p-4 text-sm">{{ $attachment->original_name }}</figcaption></figure>
                            @else
                                <a href="{{ Storage::disk('public')->url($attachment->path) }}" class="break-words rounded-2xl border border-zinc-200 p-5 font-semibold dark:border-zinc-700" target="_blank" rel="noopener noreferrer">{{ $attachment->original_name }} &darr;</a>
                            @endif
                        @empty
                            <p class="text-zinc-500">Belum ada lampiran dokumentasi.</p>
                        @endforelse
                    </div></section>
                @else
                    <p class="rounded-2xl bg-zinc-50 p-6 text-zinc-500 dark:bg-zinc-800">Dokumentasi kegiatan belum diterbitkan.</p>
                @endif
                <flux:button :href="route('schools.activities.show', [$school, $activity->id])">Detail kegiatan</flux:button>
            @elseif ($activity && $school)
                <flux:button variant="primary" :href="route('schools.documentation.show', [$school, $activity->id])">Lihat dokumentasi</flux:button>
            @endif
        </article>
        @if(isset($subActivities) && $subActivities->isNotEmpty())
            <section class="mt-6 rounded-3xl border border-zinc-200 bg-white p-6 sm:p-10 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-2xl font-bold">Subagenda / cabang kegiatan</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach($subActivities as $subActivity)
                        <article class="space-y-3 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                            <h3 class="text-lg font-semibold">{{ $subActivity->title }}</h3>
                            <p class="text-sm text-zinc-500">{{ $subActivity->start_at->format('d-m-Y H:i') }} – {{ $subActivity->end_at?->format('d-m-Y H:i') }}</p>
                            @if($subActivity->location)<p class="text-sm">{{ $subActivity->location }}</p>@endif
                            <flux:button :href="$school ? route('schools.activities.show', [$school, $subActivity->id]) : route('public.activities.show', $subActivity->id)">Detail subagenda</flux:button>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
        @foreach ($results ?? [] as $result)
            <section class="mt-6 space-y-5 rounded-3xl border border-zinc-200 bg-white p-6 sm:p-10 dark:border-zinc-800 dark:bg-zinc-900">
                <div><p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Tiga peserta teratas</p><h2 class="mt-2 text-2xl font-bold">{{ $result['assessment']->title }}</h2></div>
                <p class="text-sm text-zinc-500">{{ $result['assessment']->is_special ? 'Rata-rata nilai juri yang sudah final. Hasil dapat berubah selama penjurian berlangsung.' : 'Nilai peserta yang sudah dinilai.' }}</p>
                @include('landing.ranking-table', ['rankings' => $result['rankings']])
                <flux:button variant="primary" :href="$school ? route('schools.activities.results', [$school, $activity->id, $result['assessment']->id]) : route('public.activities.results', [$activity->id, $result['assessment']->id])">Detail seluruh perolehan nilai</flux:button>
            </section>
        @endforeach
    </main>
    @fluxScripts
</body>
</html>
