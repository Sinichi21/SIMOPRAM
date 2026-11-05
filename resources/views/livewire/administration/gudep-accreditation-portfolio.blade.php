<div class="mx-auto max-w-7xl space-y-6 p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-medium text-emerald-700">Administrasi Gudep · Akreditasi</p>
            <h1 class="mt-1 text-2xl font-bold">Portofolio Akreditasi Gudep</h1>
            <p class="mt-1 text-sm text-zinc-500">Ringkasan bukti per Gudep dan tahun ajaran, dikelompokkan menurut golongan. Data tetap berasal dari modul sumber, tidak disalin.</p>
        </div>
        <a href="{{ route('modules.show', 'administrasi') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Kembali ke Portal</a>
    </div>

    <section class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-2">
        <div>
            <label for="accreditation-gudep" class="mb-1 block text-sm font-medium">Gugusdepan</label>
            <select id="accreditation-gudep" wire:model.live="scoutGroupId" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                <option value="">Pilih Gudep</option>
                @foreach ($gudepOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="accreditation-year" class="mb-1 block text-sm font-medium">Tahun ajaran</label>
            <select id="accreditation-year" wire:model.live="academicYearId" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                <option value="">Pilih Tahun Ajaran</option>
                @foreach ($years as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
    </section>

    @if ($gudep && $year)
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Profil penilaian: {{ $gudep->name }}</h2>
            <p class="mt-1 text-sm text-zinc-500">Periode {{ $year->name }}. Ringkasan ini bukan sertifikat atau nilai akreditasi resmi.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Program lintas golongan</p><p class="mt-1 text-2xl font-bold">{{ $shared['programs'] }}</p></div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Prestasi Gudep</p><p class="mt-1 text-2xl font-bold">{{ $shared['achievements'] }}</p></div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Status buku kas</p><p class="mt-1 text-lg font-semibold">{{ $shared['cash_period'] ?? 'Belum dibuka' }}</p></div>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($levels as $level)
                <section wire:key="accreditation-level-{{ $level['name'] }}" class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold">{{ $level['name'] }}</h2>
                    <dl class="mt-3 grid grid-cols-3 gap-3 text-center">
                        <div><dt class="text-xs text-zinc-500">Formasi</dt><dd class="mt-1 text-xl font-bold">{{ $level['formations'] }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Satuan</dt><dd class="mt-1 text-xl font-bold">{{ $level['units'] }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Program</dt><dd class="mt-1 text-xl font-bold">{{ $level['programs'] }}</dd></div>
                    </dl>
                </section>
            @endforeach
        </div>

        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-200">
            <h2 class="font-semibold">Tahap fondasi Phase 6</h2>
            <p class="mt-1">Jumlah pada kartu merupakan inventaris bukti internal, bukan skor akreditasi. Matriks indikator resmi, verifikasi bukti per indikator, evaluasi kelengkapan, dan bundel dokumen PDF akan ditambahkan setelah pemetaan standar disepakati.</p>
        </section>
    @else
        <p class="rounded-xl border border-zinc-200 p-5 text-sm text-zinc-500 dark:border-zinc-700">Pilih Gudep dan tahun ajaran untuk melihat portofolio.</p>
    @endif
</div>
