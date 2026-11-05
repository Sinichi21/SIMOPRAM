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

    @if (session('status'))<p role="status" class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p>@endif
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

        <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <h2 class="text-lg font-semibold">Matriks indikator internal</h2>
                <p class="mt-1 text-sm text-zinc-500">Indikator sementara diisi secara internal, bukan daftar baku atau skor akreditasi resmi. Bukti tersimpan sebagai referensi ke data asal.</p>
            </div>
            @can('gudep.manage')
                <form wire:submit="saveIndicator" class="grid gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-4">
                    <label class="text-sm">Kode indikator
                        <input wire:model="indicatorCode" maxlength="80" required placeholder="ADM-01" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                        @error('indicatorCode') <span class="text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm md:col-span-2">Nama indikator
                        <input wire:model="indicatorTitle" maxlength="255" required placeholder="Contoh: Ketersediaan program kerja" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                        @error('indicatorTitle') <span class="text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm">Golongan
                        <select wire:model="indicatorLevelId" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="">Seluruh Gudep</option>
                            @foreach ($levels as $level)
                                @if ($level['id'] !== null)
                                    <option value="{{ $level['id'] }}">{{ $level['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                    <div class="md:col-span-4">
                        <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Tambah indikator</button>
                    </div>
                </form>
            @endcan

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 dark:border-zinc-700">
                        <tr><th class="p-2">Kode</th><th class="p-2">Indikator</th><th class="p-2">Golongan</th><th class="p-2">Bukti</th><th class="p-2">Status</th><th class="p-2">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($indicators as $indicator)
                            <tr wire:key="indicator-{{ $indicator->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="p-2 font-mono">{{ $indicator->code }}</td>
                                <td class="p-2">{{ $indicator->title }}</td>
                                <td class="p-2">{{ collect($levels)->firstWhere('id', $indicator->scout_level_id)['name'] ?? 'Seluruh Gudep' }}</td>
                                <td class="p-2">{{ $indicator->evidenceLinks->count() }}</td>
                                <td class="p-2">{{ $indicator->status === 'reviewed' ? 'Ditinjau internal' : 'Draf' }}</td>
                                <td class="p-2">
                                    <button type="button" wire:click="selectIndicator({{ $indicator->id }})" class="text-emerald-700 underline">Lihat bukti</button>
                                    @if ($indicator->status !== 'reviewed' && $indicator->evidenceLinks->isNotEmpty())
                                        @can('gudep.manage')
                                            <button type="button" wire:click="markReviewed({{ $indicator->id }})" wire:confirm="Tandai sudah ditinjau secara internal? Bukti akan dikunci." class="ml-2 text-blue-700 underline">Tinjau</button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-4 text-center text-zinc-500">Belum ada indikator untuk Gudep dan tahun ajaran ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($selectedIndicator)
                <div class="space-y-3 rounded-xl border border-emerald-200 p-4 dark:border-emerald-900">
                    <h3 class="font-semibold">Bukti indikator: {{ $selectedIndicator->code }} — {{ $selectedIndicator->title }}</h3>
                    <div class="space-y-2 text-sm">
                        @forelse ($selectedIndicator->evidenceLinks as $link)
                            <div class="flex items-center justify-between gap-3 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                                <span>{{ match ($link->source_kind) { 'program' => 'Program kerja', 'achievement' => 'Prestasi', 'cash_period' => 'Periode buku kas', 'document' => 'Dokumen terbit', default => 'Sumber' } }} #{{ $link->source_id }}</span>
                                @if ($selectedIndicator->status !== 'reviewed')
                                    @can('gudep.manage')
                                        <button wire:click="removeEvidence({{ $link->id }})" wire:confirm="Lepas referensi bukti ini?" class="text-red-600 underline">Lepas</button>
                                    @endcan
                                @endif
                            </div>
                        @empty
                            <p class="text-zinc-500">Belum ada bukti tertaut.</p>
                        @endforelse
                    </div>

                    @if ($selectedIndicator->status !== 'reviewed')
                        @can('gudep.manage')
                            <form wire:submit="linkEvidence" class="grid gap-3 sm:grid-cols-3">
                                <label class="text-sm">Jenis sumber
                                    <select wire:model.live="evidenceKind" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                                        <option value="program">Program kerja</option>
                                        <option value="achievement">Prestasi</option>
                                        <option value="cash_period">Periode buku kas</option>
                                        @can('report_verifications.view')
                                            <option value="document">Dokumen PDF terbit</option>
                                        @endcan
                                    </select>
                                </label>
                                <label class="text-sm">Data sumber
                                    <select wire:model="evidenceSourceId" required class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                                        <option value="">Pilih data</option>
                                        @foreach ($evidenceOptions as $source)
                                            <option value="{{ $source->id }}">#{{ $source->id }} — {{ $source->title ?? $source->status ?? $source->name ?? 'Catatan' }}</option>
                                        @endforeach
                                    </select>
                                    @error('evidenceSourceId') <span class="text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <div class="flex items-end">
                                    <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Tautkan bukti</button>
                                </div>
                            </form>
                        @endcan
                    @endif
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-200">
            <h2 class="font-semibold">Tahap fondasi Phase 6</h2>
            <p class="mt-1">Jumlah pada kartu merupakan inventaris bukti internal, bukan skor akreditasi. Matriks indikator resmi, verifikasi bukti per indikator, evaluasi kelengkapan, dan bundel dokumen PDF akan ditambahkan setelah pemetaan standar disepakati.</p>
        </section>
    @else
        <p class="rounded-xl border border-zinc-200 p-5 text-sm text-zinc-500 dark:border-zinc-700">Pilih Gudep dan tahun ajaran untuk melihat portofolio.</p>
    @endif
</div>
