<!DOCTYPE html>
<html lang="id">
<head>@include('partials.head', ['title' => 'Hasil '.$assessment->title])</head>
<body class="simpram-app min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <x-public-header :school="$school" />
    <main class="mx-auto max-w-5xl space-y-6 px-5 py-10">
        <a href="{{ $school ? route('schools.activities.show', [$school, $activity->id]) : route('public.activities.show', $activity->id) }}" class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">&larr; Kembali ke detail kegiatan</a>
        <header class="app-page-heading"><p class="text-sm">{{ $activity->title }}</p><h1 class="mt-3 text-3xl">{{ $assessment->title }}</h1><p class="mt-3">Seluruh perolehan nilai</p></header>
        <section class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-5 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $assessment->is_special ? 'Nilai merupakan rata-rata berbobot dari juri yang sudah final. Draft juri tidak dihitung. Hasil dapat berubah selama penjurian berlangsung.' : 'Hasil menggunakan nilai kegiatan yang telah disimpan oleh penilai.' }} Nilai sama mendapat peringkat sama (contoh: 1, 1, 3).</p>
            @include('landing.ranking-table', ['rankings' => $rankings])
            <h2 class="text-lg font-semibold">Kriteria penilaian</h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach ($assessment->criteria as $criterion)
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800"><dt class="font-semibold">{{ $criterion->name }}</dt><dd class="mt-1 text-sm text-zinc-500">Nilai maksimal {{ $criterion->max_score }} · Bobot {{ $criterion->weight }}%</dd></div>
                @endforeach
            </dl>
        </section>
    </main>
    @fluxScripts
</body>
</html>
