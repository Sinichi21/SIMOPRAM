<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="referrer" content="no-referrer">
    @include('partials.head', ['title' => 'Penilaian Juri'])
</head>
<body class="simpram-app min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <x-brand-logo class="h-10 w-auto max-w-40" />
            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Penilaian Juri</span>
        </div>
    </header>
    <main class="app-content mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
        <section class="app-page-heading space-y-4">
            <span class="inline-flex rounded-full px-3 py-1 text-xs">Form Penilaian Juri</span>
            <div>
                <h1 class="text-2xl break-words sm:text-3xl">{{ $assessment->title }}</h1>
                <p class="mt-2 text-sm">{{ $assessment->activity->title }}</p>
            </div>
            <dl class="grid gap-4 border-t border-emerald-800 pt-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-emerald-200">Juri</dt>
                    <dd class="mt-1 font-semibold break-words">{{ $judge->name }}</dd>
                </div>
                <div>
                    <dt class="text-emerald-200">Batas pengisian</dt>
                    <dd class="mt-1 font-semibold">{{ $judge->expires_at->format('d-m-Y H:i') }} ({{ config('app.timezone') }})</dd>
                </div>
            </dl>
        </section>
        @if (session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                <p class="font-semibold">Periksa kembali nilai yang diisi.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Petunjuk pengisian</h2>
            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">Isi nilai sesuai kriteria dan batas nilai maksimal. Simpan draft untuk melanjutkan nanti. Setelah finalisasi, nilai terkunci dan link ini tidak dapat digunakan kembali.</p>
            <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium">
                <span class="rounded-full bg-zinc-100 px-3 py-1.5 dark:bg-zinc-800">{{ $assessment->targets->count() }} peserta</span>
                <span class="rounded-full bg-zinc-100 px-3 py-1.5 dark:bg-zinc-800">{{ $assessment->criteria->count() }} kriteria penilaian</span>
            </div>
        </section>
        <form method="POST" action="{{ route('activity-judges.store', ['token' => $token]) }}" class="space-y-6">
            @csrf
            @foreach ($assessment->targets as $target)
                <fieldset class="min-w-0 rounded-xl border border-zinc-200 bg-white p-4 sm:p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <legend class="max-w-full px-2 text-base font-semibold break-words">
                        <span class="mr-2 inline-flex size-7 items-center justify-center rounded-full bg-zinc-100 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $loop->iteration }}</span>
                        {{ $target->participant_name ?? $target->student?->name ?? $target->scoutUnit?->name ?? 'Peserta' }}
                    </legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach ($assessment->criteria as $criterion)
                            <div class="min-w-0 space-y-2">
                                <label for="score-{{ $target->id }}-{{ $criterion->id }}" class="block">{{ $criterion->name }}</label>
                                <div id="score-help-{{ $target->id }}-{{ $criterion->id }}" class="space-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    <p>Maks. {{ $criterion->max_score }} &middot; Bobot {{ $criterion->weight }}%</p>
                                    @if ($criterion->description)
                                        <p class="leading-5">{{ $criterion->description }}</p>
                                    @endif
                                </div>
                                <input
                                    id="score-{{ $target->id }}-{{ $criterion->id }}"
                                    type="number" min="0" max="{{ $criterion->max_score }}" step="0.01" inputmode="decimal"
                                    name="scores[{{ $target->id }}][{{ $criterion->id }}]"
                                    value="{{ old('scores.'.$target->id.'.'.$criterion->id, $judge->scores[$target->id][$criterion->id] ?? '') }}"
                                    aria-describedby="score-help-{{ $target->id }}-{{ $criterion->id }}"
                                    placeholder="Masukkan nilai"
                                    class="block w-full rounded-lg border border-zinc-300 p-3 tabular-nums"
                                >
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
            <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800 dark:bg-zinc-900">
                <p class="max-w-md text-sm leading-6 text-zinc-600 dark:text-zinc-300">Pastikan seluruh nilai sudah benar sebelum finalisasi. Nilai yang sudah final tidak dapat diubah.</p>
                <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
                    <button type="submit" name="action" value="save" class="rounded-lg border border-zinc-300 px-4 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">Simpan Draft</button>
                    <button type="submit" name="action" value="finalize" onclick="return confirm('Finalisasi seluruh nilai? Nilai tidak dapat diubah dan akses link akan ditutup.')" class="rounded-lg bg-emerald-800 px-4 py-2 text-white hover:bg-emerald-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 dark:bg-emerald-300 dark:text-emerald-950 dark:hover:bg-emerald-200">Finalisasi Nilai</button>
                </div>
            </div>
        </form>
    </main>
</body>
</html>
