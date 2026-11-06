<div class="mx-auto max-w-7xl space-y-6 p-5 sm:p-7">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Administrasi Gugusdepan · Program Kerja v2</p>
            <h1 class="mt-1 text-2xl font-bold">Program Tahunan, Semester & Latihan Mingguan</h1>
            <p class="mt-2 max-w-3xl text-sm text-zinc-500">Form mengikuti kelompok dan tabel buku administrasi. Identitas Gudep dan tahun ajaran selalu diambil dari SIMPRAM; jumlah kegiatan boleh kosong (ditampilkan –).</p>
        </div>
        <a href="{{ route('administration.development') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Pembinaan Gudep</a>
    </header>

    @if (session('status'))
        <div role="status" class="rounded-xl border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">{{ session('status') }}</div>
    @endif

    <section aria-label="Lingkup Gudep" class="grid gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:grid-cols-2">
        <label class="text-sm font-medium">Gugusdepan aktif
            <select wire:model.live="scoutGroupId" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                <option value="">Pilih Gudep</option>
                @foreach ($gudepOptions as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach
            </select>
        </label>
        <label class="text-sm font-medium">Tahun ajaran
            <select wire:model.live="academicYearId" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                <option value="">Pilih tahun ajaran</option>
                @foreach ($years as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach
            </select>
        </label>
    </section>

    @if ($gudep && $year)
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach (['annual' => 'Program Tahunan', 'semester' => 'Program Semester', 'weekly' => 'Latihan Mingguan'] as $type => $label)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-sm text-zinc-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ $sheets->where('kind', $type)->count() }}</p>
                </div>
            @endforeach
        </div>

        <section aria-label="Status rencana program" class="grid gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:grid-cols-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Total materi / baris</p>
                <p class="mt-1 text-xl font-bold tabular-nums">{{ $sheets->sum(fn ($sheet) => count($sheet->entries ?? [])) }}</p>
                <p class="text-xs text-zinc-500">Nomor urut tetap mengikuti masing-masing bagian dokumen.</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Latihan terkait agenda</p>
                <p class="mt-1 text-xl font-bold tabular-nums">{{ $sheets->where('kind', 'weekly')->whereNotNull('activity_id')->count() }}</p>
                <p class="text-xs text-zinc-500">Dihitung dari relasi kegiatan SIMPRAM.</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Golongan tersedia</p>
                <p class="mt-1 text-xl font-bold tabular-nums">{{ $levels->count() }}</p>
                <p class="text-xs text-zinc-500">{{ $levels->pluck('name')->join(', ') ?: 'Belum ada golongan aktif' }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-bold">Formulir Program Kerja</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <label class="text-sm font-medium">Format formulir
                    <select wire:model.live="kind" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                        <option value="annual">Program Kerja Tahunan</option>
                        <option value="semester">Program Kerja Semester</option>
                        <option value="weekly">Program Latihan Mingguan</option>
                    </select>
                </label>
                <label class="text-sm font-medium">Judul program
                    <input wire:model="title" maxlength="200" placeholder="Contoh: Program Kerja Gudep 2026/2027" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                    @error('title')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
            </div>

            <div class="mt-4 grid gap-3 rounded-xl bg-zinc-50 p-4 text-sm dark:bg-zinc-800/50 sm:grid-cols-2">
                <div>
                    <span class="block text-xs text-zinc-500">Gugusdepan (dari sistem · terkunci)</span>
                    <span class="font-semibold">{{ $gudep->name }}</span>
                    <p class="text-xs text-zinc-500">Putra: {{ $gudep->male_number ?: '–' }} · Putri: {{ $gudep->female_number ?: '–' }}</p>
                </div>
                <div>
                    <span class="block text-xs text-zinc-500">Tahun ajaran (dari sistem · terkunci)</span>
                    <span class="font-semibold">{{ $year->name }}</span>
                </div>
            </div>

            @if ($kind !== 'annual')
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="text-sm font-medium">Semester
                        <select wire:model="semesterId" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="">Pilih semester</option>
                            @foreach ($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->name }}</option>@endforeach
                        </select>
                        @error('semesterId')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm font-medium">Golongan / satuan
                        <select wire:model="scoutLevelId" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="">Pilih golongan yang tersedia</option>
                            @foreach ($levels as $level)<option value="{{ $level->id }}">{{ $level->name }}</option>@endforeach
                        </select>
                        @error('scoutLevelId')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                        @if ($levels->isEmpty())<span class="mt-1 block text-xs text-amber-700">Belum ada satuan aktif pada tahun ini. Lengkapi satuan dahulu.</span>@endif
                    </label>
                    <label class="text-sm font-medium">Tautkan ke program induk (opsional)
                        <select wire:model="parentId" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="">Belum ditautkan</option>
                            @foreach ($sheets->where('kind', $kind === 'semester' ? 'annual' : 'semester') as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->title }}</option>
                            @endforeach
                        </select>
                        @error('parentId')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    @if ($kind === 'weekly')
                        <label class="text-sm font-medium">Kaitkan kegiatan SIMPRAM (opsional)
                            <select wire:model.live="activityId" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                                <option value="">Belum ditautkan</option>
                                @foreach ($activities as $activity)
                                    <option value="{{ $activity->id }}">{{ $activity->title }} · {{ $activity->start_at?->format('d/m/Y') ?? '–' }}</option>
                                @endforeach
                            </select>
                            @error('activityId')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        @if ($linkedActivity)
                            <div class="rounded-xl bg-zinc-50 p-3 text-sm dark:bg-zinc-800/50" aria-label="Kegiatan terhubung dari SIMPRAM">
                                <p class="text-xs font-semibold text-zinc-500">Data kegiatan dari SIMPRAM · terkunci</p>
                                <p class="mt-1 font-semibold">{{ $linkedActivity->title }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $linkedActivity->start_at?->format('d/m/Y H:i') ?? 'Jadwal belum ditetapkan' }} · {{ $linkedActivity->location ?: 'Lokasi belum ditentukan' }}</p>
                                <a href="{{ route('activities.index') }}" wire:navigate class="mt-2 inline-block text-xs font-semibold text-emerald-700 dark:text-emerald-300">Kelola di Modul Kegiatan</a>
                            </div>
                        @endif
                    @endif
                    @if ($kind === 'weekly')
                        <label class="text-sm font-medium">Tanggal latihan
                            <input type="date" wire:model="scheduledFor" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            @error('scheduledFor')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    @endif
                </div>
            @endif

            @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage'))
                <form wire:submit="save" class="mt-6 space-y-5">
                    @if ($kind === 'annual')
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <p class="font-semibold text-emerald-900 dark:text-emerald-200">Susunan Program Kerja Tahunan berdasarkan buku administrasi</p>
                            <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">A–F, penomoran bertingkat, uraian tambahan, jumlah kegiatan opsional, dan rencana anggaran. Gunakan tombol subbaris untuk membuat daftar di dalam daftar.</p>
                            @if (count($entries) === 0)
                                <button type="button" wire:click="loadAnnualTemplate" class="mt-3 rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white">Isi contoh sesuai golongan aktif</button>
                            @endif
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="text-center font-semibold uppercase">
                                <p>PROGRAM KERJA TAHUNAN</p>
                                <p>GUGUS DEPAN {{ mb_strtoupper(trim(($gudep->kwarcab ?: '').' '.($gudep->male_number ?: '–').'-'.($gudep->female_number ?: '–'))) }}</p>
                                <p>TAHUN {{ str_replace('/', '-', $year->name) }}</p>
                            </div>
                        </div>
                        @foreach ($annualSections as $section => $heading)
                            @php
                                $rows = $annualPreviewRows[$section] ?? [];
                                $isLevel = in_array($section, ['siaga', 'penggalang', 'penegak', 'pandega'], true);
                                $levelNumber = ['siaga' => 1, 'penggalang' => 2, 'penegak' => 3, 'pandega' => 4][$section] ?? null;
                            @endphp
                            <section wire:key="section-{{ $section }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                                    <div>
                                        @if ($section === 'siaga')
                                            <h3 class="font-bold">A. BIDANG KEGIATAN DAN LATIHAN PESERTA DIDIK</h3>
                                        @elseif (! $isLevel)
                                            <h3 class="font-bold">{{ $heading }}</h3>
                                        @endif
                                        @if ($isLevel)
                                            <p class="ml-5 font-medium">{{ $levelNumber }}. {{ ucfirst($section) }}</p>
                                        @endif
                                    </div>
                                    <button type="button" wire:click="addRow('{{ $section }}')" class="rounded-lg border border-emerald-600 px-3 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300">+ Tambah butir</button>
                                </div>
                                @if ($section === 'keuangan')
                                    <p class="mt-2 text-xs text-zinc-500">Rencana anggaran menggunakan Rupiah (IDR). Saran realisasi historis hanya referensi, bukan transaksi baru atau saldo kas.</p>
                                @endif
                                <div class="mt-3 space-y-3">
                                    @forelse ($rows as $row)
                                        @php $index = $row['index']; $item = $row['entry']; $depth = $row['depth']; @endphp
                                        <div wire:key="annual-row-{{ $item['key'] ?? $index }}" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50" style="margin-left:{{ $depth * 20 }}px">
                                            <div class="flex items-start gap-2">
                                                <span class="w-7 shrink-0 pt-2 text-sm font-semibold">{{ $row['marker'] }}</span>
                                                <div class="min-w-0 flex-1 space-y-2">
                                                    <input wire:model="entries.{{ $index }}.description" maxlength="500" placeholder="Uraian butir program" class="w-full rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800" aria-label="Butir {{ $row['marker'] }} {{ $heading }}">
                                                    @error('entries.'.$index.'.description')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                    <textarea wire:model="entries.{{ $index }}.detail" maxlength="1500" rows="2" placeholder="Deskripsi / pengantar subdaftar (opsional), misalnya: Berusaha untuk pencapaian SKK 10 macam meliputi..." class="w-full rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800"></textarea>
                                                    <div class="flex flex-wrap items-end gap-2">
                                                        @if ($section !== 'keuangan')
                                                            <label class="text-xs text-zinc-600 dark:text-zinc-300">Jumlah kegiatan
                                                                <input type="number" min="0" wire:model="entries.{{ $index }}.quantity" placeholder="–" class="mt-1 block w-24 rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                                            </label>
                                                            <label class="text-xs text-zinc-600 dark:text-zinc-300">Satuan
                                                                <select wire:model="entries.{{ $index }}.unit" class="mt-1 block rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                                                    <option value="kali">kali</option><option value="buah">buah</option><option value="orang">orang</option><option value="paket">paket</option>
                                                                </select>
                                                            </label>
                                                        @else
                                                            <label class="text-xs text-zinc-600 dark:text-zinc-300">Jenis rencana
                                                                <select wire:model.live="entries.{{ $index }}.finance_type" class="mt-1 block rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                                                    <option value="">Judul / uraian</option><option value="income">Pemasukan</option><option value="expense">Pengeluaran</option><option value="fundraising">Usaha Dana</option>
                                                                </select>
                                                            </label>
                                                            @if (in_array($item['finance_type'] ?? '', ['income','expense'], true))
                                                                <label class="text-xs text-zinc-600 dark:text-zinc-300">Nilai (Rp)
                                                                    <input type="number" min="0" step="1" wire:model="entries.{{ $index }}.amount" placeholder="–" class="mt-1 block w-44 rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                                                </label>
                                                                <span class="rounded-md bg-zinc-200 px-2 py-2 text-xs dark:bg-zinc-700">IDR · Rupiah</span>
                                                                @if ($historicalSuggestions !== [])
                                                                    <div class="w-full">
                                                                        <p class="mb-1 text-xs text-zinc-500">Saran dari buku kas tahun sebelumnya (klik untuk mengisi angka):</p>
                                                                        <div class="flex flex-wrap gap-1">
                                                                            @foreach ($historicalSuggestions as $suggestion)
                                                                                @if ($suggestion['direction'] === ($item['finance_type'] ?? null))
                                                                                    <button type="button" wire:click='useBudgetSuggestion({{ $index }}, @js($suggestion["category"]))' class="rounded-md border border-dashed border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600">{{ $suggestion['category'] }} · Rp {{ number_format($suggestion['amount'], 0, ',', '.') }}</button>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        @endif
                                                        @if ($depth < 2)
                                                            <button type="button" wire:click="addChildRow({{ $index }})" class="rounded-md border border-zinc-300 px-2 py-2 text-xs font-medium dark:border-zinc-600">+ Subbutir</button>
                                                        @endif
                                                        <button type="button" wire:click="removeRow({{ $index }})" wire:confirm="Hapus butir ini beserta subbutirnya?" class="rounded-md border border-red-300 px-2 py-2 text-xs font-medium text-red-600">Hapus</button>
                                                    </div>
                                                    @error('entries.'.$index.'.amount')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-zinc-500">{{ $isLevel ? 'Golongan ini boleh dikosongkan apabila tidak tersedia.' : 'Belum ada butir. Gunakan tombol Tambah butir.' }}</p>
                                    @endforelse
                                </div>
                                @if ($section === 'keuangan')
                                    @php $total = \App\Support\GudepAnnualProgram::totals($entries); @endphp
                                    <div class="mt-4 grid gap-2 rounded-lg bg-zinc-100 p-3 text-sm dark:bg-zinc-800 sm:grid-cols-2">
                                        <div>Rencana Pemasukan: <strong>Rp {{ number_format($total['income'], 0, ',', '.') }}</strong></div>
                                        <div>Rencana Pengeluaran: <strong>Rp {{ number_format($total['expense'], 0, ',', '.') }}</strong></div>
                                        <div>Kekurangan / Usaha Dana: <strong>Rp {{ number_format($total['shortfall'], 0, ',', '.') }}</strong></div>
                                        <div>Surplus: <strong>Rp {{ number_format($total['surplus'], 0, ',', '.') }}</strong></div>
                                    </div>
                                @endif
                            </section>
                        @endforeach
                    @elseif ($kind === 'semester')
                        <section class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <table class="min-w-full text-left text-xs">
                                <thead class="bg-zinc-50 dark:bg-zinc-800"><tr><th class="p-3">No.</th><th class="min-w-52 p-3">Kegiatan</th>@for ($month=1; $month<=6; $month++)<th class="p-2 text-center">{{ $month }}</th>@endfor<th class="p-3">Jumlah anggota</th><th class="p-3">Ket.</th><th class="p-3"></th></tr></thead>
                                <tbody>
                                    @foreach ($entries as $index => $row)
                                        <tr wire:key="semester-{{ $index }}" class="border-t border-zinc-100 dark:border-zinc-800">
                                            <td class="p-2">{{ $loop->iteration }}.</td>
                                            <td class="p-2"><input wire:model="entries.{{ $index }}.description" class="w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">@error('entries.'.$index.'.description')<span class="block text-red-600">{{ $message }}</span>@enderror</td>
                                            @for ($month=0; $month<6; $month++)<td class="p-2 text-center"><input type="checkbox" wire:model="entries.{{ $index }}.months.{{ $month }}" aria-label="Bulan {{ $month+1 }} baris {{ $index + 1 }}"></td>@endfor
                                            <td class="p-2"><input type="number" min="0" placeholder="–" wire:model="entries.{{ $index }}.participants" class="w-20 rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></td>
                                            <td class="p-2"><input wire:model="entries.{{ $index }}.notes" class="w-32 rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></td>
                                            <td class="p-2"><button type="button" wire:click="removeRow({{ $index }})" class="text-red-600">Hapus</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </section>
                        <button type="button" wire:click="addRow" class="rounded-lg border border-emerald-600 px-3 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">+ Tambah kegiatan</button>
                    @else
                        <section class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-zinc-50 dark:bg-zinc-800"><tr><th class="p-3">No.</th><th class="p-3">Uraian latihan</th><th class="p-3">SKU</th><th class="p-3">SKK</th><th class="p-3">Keterangan</th><th class="p-3"></th></tr></thead>
                                <tbody>
                                    @foreach ($entries as $index => $row)
                                        <tr wire:key="weekly-{{ $index }}" class="border-t border-zinc-100 dark:border-zinc-800">
                                            <td class="p-2">{{ $loop->iteration }}.</td>
                                            @foreach (['description', 'sku', 'skk', 'notes'] as $field)
                                                <td class="p-2"><input wire:model="entries.{{ $index }}.{{ $field }}" placeholder="{{ $field === 'description' ? 'Materi latihan' : '–' }}" class="min-w-28 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></td>
                                            @endforeach
                                            <td class="p-2"><button type="button" wire:click="removeRow({{ $index }})" class="text-red-600">Hapus</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </section>
                        <button type="button" wire:click="addRow" class="rounded-lg border border-emerald-600 px-3 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">+ Tambah materi</button>
                    @endif
                    @error('entries')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <label class="block text-sm font-medium">Catatan tambahan
                        <textarea wire:model="notes" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></textarea>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white">Simpan {{ $editingId ? 'Perubahan' : 'Program Kerja' }}</button>
                        @if ($editingId)<button type="button" wire:click="resetDraft" class="rounded-lg border border-zinc-300 px-4 py-2">Batal edit</button>@endif
                    </div>
                </form>
            @else
                <p class="mt-4 text-sm text-zinc-500">Akun ini memiliki akses lihat saja. Perubahan memerlukan izin pengelolaan pembinaan Gudep.</p>
            @endif
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-bold">Dashboard Daftar Program</h2>
            <p class="mt-1 text-sm text-zinc-500">Ringkasan program tersimpan berdasarkan Gudep dan tahun ajaran, tanpa membuat ulang data anggota/satuan.</p>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead><tr class="border-b text-zinc-500"><th class="p-2">Jenis</th><th class="p-2">Program</th><th class="p-2">Golongan</th><th class="p-2">Jumlah baris</th><th class="p-2">Agenda terhubung</th><th class="p-2">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($sheets as $sheet)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="p-2">{{ ['annual' => 'Tahunan', 'semester' => 'Semester', 'weekly' => 'Mingguan'][$sheet->kind] ?? $sheet->kind }}</td>
                                <td class="p-2 font-medium">{{ $sheet->title }} <span class="block text-xs font-normal text-zinc-500">{{ $sheet->scheduled_for?->format('d/m/Y') ?? '–' }}</span></td>
                                <td class="p-2">{{ $levels->firstWhere('id', $sheet->scout_level_id)?->name ?? 'Seluruh Gudep / –' }}</td>
                                <td class="p-2">{{ count($sheet->entries ?? []) }}</td>
                                <td class="p-2">
                                    @if ($sheet->activity)
                                        <span class="font-medium">{{ $sheet->activity->title }}</span>
                                        <span class="block text-xs text-zinc-500">{{ $sheet->activity->start_at?->format('d/m/Y') ?? '–' }}</span>
                                    @else
                                        <span class="text-zinc-400">–</span>
                                    @endif
                                </td>
                                <td class="p-2">
                                    <button type="button" wire:click="preview({{ $sheet->id }})" class="text-emerald-700 underline dark:text-emerald-300">Lihat</button>
                                    <a href="{{ route('administration.programs.print', $sheet->id) }}" target="_blank" rel="noopener" class="ml-2 text-emerald-700 underline dark:text-emerald-300">Cetak A4</a>
                                    @if (auth()->user()?->can('reports.export') && (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage')))
                                        <button type="button" wire:click="publishProgram({{ $sheet->id }})" wire:confirm="Terbitkan PDF resmi Program Kerja ini? Perubahan isi berikutnya akan menjadi revisi dokumen." class="ml-2 font-semibold text-emerald-700 underline dark:text-emerald-300">Terbitkan PDF + QR</button>
                                    @endif
                                    @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage'))
                                        <button type="button" wire:click="edit({{ $sheet->id }})" class="ml-2 text-emerald-700 underline dark:text-emerald-300">Edit</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-4 text-center text-zinc-500">Belum ada program kerja.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @if ($previewSheet)
                <div class="mt-5 space-y-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" aria-label="Pratinjau dokumen program">
                    @if ($previewSheet->kind !== 'annual')
                    <div class="border-b border-zinc-200 pb-3 text-center dark:border-zinc-700">
                        <p class="text-xs font-semibold uppercase tracking-wide">GERAKAN PRAMUKA</p>
                        <h3 class="mt-1 text-lg font-bold uppercase">{{ $previewSheet->title }}</h3>
                        <p class="text-sm">{{ $gudep->name }} · {{ $year->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $previewSheet->scheduled_for?->format('d/m/Y') ?? '–' }}</p>
                        @if ($previewSheet->activity)
                            <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-300">
                                Agenda SIMPRAM: <strong>{{ $previewSheet->activity->title }}</strong> · {{ $previewSheet->activity->start_at?->format('d/m/Y H:i') ?? '–' }}
                            </p>
                        @endif
                    </div>
                    @endif
                    @if ($previewSheet->kind === 'annual')
                        @include('administration.partials.annual-program-body', [
                            'entries' => $previewSheet->entries ?? [],
                            'kwarcab' => $gudep->kwarcab,
                            'maleNumber' => $gudep->male_number,
                            'femaleNumber' => $gudep->female_number,
                            'yearName' => $year->name,
                        ])
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-xs">
                                <thead><tr class="border-b">
                                    <th class="p-2">No.</th><th class="p-2">Kegiatan</th>
                                    @if ($previewSheet->kind === 'semester')
                                        @for ($month = 1; $month <= 6; $month++)<th class="p-2">{{ $month }}</th>@endfor
                                        <th class="p-2">Jumlah</th>
                                    @else
                                        <th class="p-2">SKU</th><th class="p-2">SKK</th>
                                    @endif
                                    <th class="p-2">Ket.</th>
                                </tr></thead>
                                <tbody>
                                    @foreach ($previewSheet->entries as $entry)
                                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                            <td class="p-2">{{ $loop->iteration }}.</td>
                                            <td class="p-2">{{ $entry['description'] }}</td>
                                            @if ($previewSheet->kind === 'semester')
                                                @foreach ($entry['months'] as $checked)<td class="p-2">{{ $checked ? '✓' : '–' }}</td>@endforeach
                                                <td class="p-2">{{ $entry['participants'] ?? '–' }}</td>
                                            @else
                                                <td class="p-2">{{ $entry['sku'] ?: '–' }}</td>
                                                <td class="p-2">{{ $entry['skk'] ?: '–' }}</td>
                                            @endif
                                            <td class="p-2">{{ $entry['notes'] ?: '–' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
            </div>
        </section>
    @endif
</div>
