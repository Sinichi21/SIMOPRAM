<div class="mx-auto max-w-7xl space-y-6 p-5 sm:p-7">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Administrasi Gugusdepan · Program Kerja v2</p>
            <h1 class="mt-1 text-2xl font-bold">Program Tahunan, Semester & Latihan Mingguan</h1>
            <p class="mt-2 max-w-3xl text-sm text-zinc-500">Form mengikuti kelompok dan tabel buku administrasi. Identitas Gudep dan tahun ajaran selalu diambil dari SIMPRAM; jumlah kegiatan boleh kosong (ditampilkan –).</p>
        </div>
        <a href="{{ route('administration.development') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Progres SKU/SKK & Prestasi</a>
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
                @if ($kind === 'annual')
                    <label class="text-sm font-medium">Program milik Gugusdepan
                        <select wire:model.live="letterheadType" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="male">Gudep Putra ({{ $gudep->male_number ?: 'nomor belum diatur' }})</option>
                            <option value="female">Gudep Putri ({{ $gudep->female_number ?: 'nomor belum diatur' }})</option>
                            @if ($editingId && $letterheadType === 'mabigus')
                                <option value="mabigus">Data lama — Gabungan (ubah ke Putra/Putri untuk revisi)</option>
                            @endif
                        </select>
                        <span class="mt-1 block text-xs text-zinc-500">Program Tahunan diterbitkan per Gudep Putra atau Putri, bukan atas nama Mabigus.</span>
                    </label>
                @endif
                <label class="text-sm font-medium">Judul program
                    <input wire:model="title" maxlength="200" placeholder="Contoh: Program Kerja Gudep 2026/2027" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                    @error('title')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
            </div>

            <div class="mt-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900">
                @if ($letterheadProfile)
                    <p class="mb-2 text-xs font-medium text-zinc-500">Pratinjau kop berdasarkan administrasi {{ $letterheadProfile['type'] === 'mabigus' ? 'Mabigus' : ($letterheadProfile['type'] === 'male' ? 'Putra' : 'Putri') }} — data dari sistem</p>
                    @include('administration.partials.gudep-document-letterhead', ['letterhead' => $letterheadProfile])
                @endif
            </div>
            <div class="mt-4 grid gap-3 rounded-xl bg-zinc-50 p-4 text-sm dark:bg-zinc-800/50 sm:grid-cols-2">
                <div>
                    <span class="block text-xs text-zinc-500">Gugusdepan (dari sistem · terkunci)</span>
                    <span class="font-semibold">{{ $gudep->name }}</span>
                    <p class="text-xs text-zinc-500">Pemilik program: {{ $letterheadType === 'male' ? 'Gudep Putra' : ($letterheadType === 'female' ? 'Gudep Putri' : 'Gabungan (data lama)') }}
                        — {{ $letterheadType === 'male' ? ($letterheadProfile['male_number'] ?? '–') : ($letterheadType === 'female' ? ($letterheadProfile['female_number'] ?? '–') : '–') }}
                    </p>
                </div>
                <div>
                    <span class="block text-xs text-zinc-500">Tahun ajaran (dari sistem · terkunci)</span>
                    <span class="font-semibold">{{ $year->name }}</span>
                </div>
            </div>

            @if ($kind !== 'annual')
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="text-sm font-medium">Semester
                        <select wire:model.live="semesterId" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="">Pilih semester</option>
                            @foreach ($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->name }}</option>@endforeach
                        </select>
                        @error('semesterId')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm font-medium">Golongan / satuan
                        <select wire:model.live="scoutLevelId" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="">Pilih golongan yang tersedia</option>
                            @foreach ($levels as $level)<option value="{{ $level->id }}">{{ $level->name }}</option>@endforeach
                        </select>
                        @error('scoutLevelId')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                        @if ($levels->isEmpty())<span class="mt-1 block text-xs text-amber-700">Belum ada satuan aktif pada tahun ini. Lengkapi satuan dahulu.</span>@endif
                    </label>
                    <label class="text-sm font-medium">Satuan asal
                        <select wire:model.live="formationId" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="">Pilih satuan pada golongan ini</option>
                            @foreach ($formations as $formation)
                                <option value="{{ $formation->id }}">{{ $formation->name }} ({{ $formation->gender ?: 'campuran/belum ditentukan' }})</option>
                            @endforeach
                        </select>
                        @error('formationId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-800/50">
                        <p class="font-medium">Kop dokumen (otomatis · terkunci)</p>
                        <p class="mt-1">{{ ['mabigus' => 'Mabigus / Gabungan', 'male' => 'Gugusdepan Putra', 'female' => 'Gugusdepan Putri'][$letterheadType] ?? '–' }}</p>
                        <p class="mt-1 text-xs text-zinc-500">Berasal dari jenis satuan yang dipilih. Ubah jenis satuan melalui Administrasi Gudep jika datanya keliru.</p>
                        @error('letterheadType')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </div>
                    <label class="text-sm font-medium">{{ $kind === 'semester' ? 'Sumber utama: Program Tahunan' : 'Tautkan ke Program Semester (opsional)' }}
                        <select wire:model.live="parentId" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="">Belum ditautkan</option>
                            @foreach ($parentOptions as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->title }}</option>
                            @endforeach
                        </select>
                        @error('parentId')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    @if ($kind === 'semester' && $parentId !== null)
                        <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-3 text-sm dark:border-emerald-900 dark:bg-emerald-950/30">
                            <p class="font-semibold">Program Semester adalah turunan Program Tahunan</p>
                            <p class="mt-1 text-xs">Jadwal dan nama kegiatan mengikuti satu sumber Tahunan. Semester Ganjil: Agustus–Desember (5 bulan); Genap: Januari–Juli (7 bulan). Yang ditambahkan di Semester hanyalah jumlah peserta, catatan pelaksanaan, atau kegiatan khusus tambahan.</p>
                            <button type="button" wire:click="importScheduledAnnualItems" class="mt-3 rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white">Ambil semua kegiatan terjadwal dari Tahunan</button>
                            @error('entries') <p class="mt-2 text-xs text-red-700">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    @if ($kind !== 'annual' && $parentId !== null && $parentItems->isNotEmpty())
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Ambil butir dari program induk</p>
                            <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">Menambahkan satu materi ke draft ini. Tidak membuat kegiatan, jurnal, atau transaksi keuangan.</p>
                            <div class="mt-2 flex flex-wrap items-end gap-2">
                                <label class="min-w-0 flex-1 text-xs">Materi dari induk
                                    <select wire:model="parentItemIndex" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                        <option value="">Pilih butir yang akan digunakan</option>
                                        @foreach ($parentItems as $parentItem)
                                            <option value="{{ $parentItem['index'] }}">{{ \Illuminate\Support\Str::limit($parentItem['description'], 110) }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <button type="button" wire:click="importParentItem" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white">+ Tambahkan ke draft</button>
                            </div>
                        </div>
                    @endif
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
                    @if ($saveNotice !== '')
                        <div role="status" class="rounded-xl border border-emerald-400 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            {{ $saveNotice }} Dokumen tetap terbuka agar perubahan dapat diperiksa.
                        </div>
                    @endif
                    @if ($errors->any())
                        <div role="alert" class="rounded-xl border border-red-400 bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">
                            <p class="font-semibold">Program belum tersimpan. Periksa {{ $errors->count() }} kesalahan berikut:</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
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
                                <p>GUGUS DEPAN {{ mb_strtoupper(trim(($gudep->kwarcab ?: '').' '.($letterheadType === 'female' ? ($letterheadProfile['female_number'] ?? '–') : ($letterheadType === 'male' ? ($letterheadProfile['male_number'] ?? '–') : (($letterheadProfile['male_number'] ?? '–').'-'.($letterheadProfile['female_number'] ?? '–')))))) }}</p>
                                <p>TAHUN {{ str_replace('/', '-', $year->name) }}</p>
                            </div>
                        </div>
                        <label class="block text-sm font-medium">Format dokumen tahunan
                            <select wire:model.live="documentLayout" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                                <option value="narrative">Format naratif A–F (format sekarang)</option>
                                <option value="monthly_matrix">Tampilan matriks Tahunan (Agustus–Juli) — sumber semester</option>
                            </select>
                            <span class="mt-1 block text-xs text-zinc-500">Kedua format memakai data rencana yang sama. Untuk matriks, tandai bulan pada setiap butir kegiatan.</span>
                        </label>
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
                                        <div wire:key="annual-row-{{ $section }}-{{ $item['key'] ?? $index }}" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50" style="margin-left:{{ $depth * 20 }}px">
                                            <div class="flex items-start gap-2">
                                                <span class="w-7 shrink-0 pt-2 text-sm font-semibold">{{ $row['marker'] }}</span>
                                                <div class="min-w-0 flex-1 space-y-2">
                                                    <input wire:key="annual-description-{{ $item['key'] ?? $index }}" wire:model="entries.{{ $index }}.description" maxlength="500" placeholder="Uraian butir program" class="w-full rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800" aria-label="Butir {{ $row['marker'] }} {{ $heading }}">
                                                    @error('entries.'.$index.'.description')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                    @if ($documentLayout === 'monthly_matrix')
                                                        <fieldset class="rounded-md border border-zinc-300 p-2 dark:border-zinc-600">
                                                            <legend class="text-xs font-semibold">Bulan pelaksanaan (Agustus–Juli)</legend>
                                                            <div class="flex flex-wrap gap-2">
                                                                @foreach ([8,9,10,11,12,1,2,3,4,5,6,7] as $monthIndex => $month)
                                                                    <label class="flex items-center gap-1 text-xs">
                                                                        <input type="checkbox" wire:model="entries.{{ $index }}.months.{{ $monthIndex }}" class="rounded">
                                                                        {{ $month }}
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        </fieldset>
                                                        <input wire:model="entries.{{ $index }}.notes" maxlength="500" placeholder="Keterangan baris matriks (opsional)" aria-label="Keterangan baris {{ $index + 1 }}" class="w-full rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                                    @endif
                                                    <textarea wire:key="annual-detail-{{ $item['key'] ?? $index }}" wire:model="entries.{{ $index }}.detail" maxlength="1500" rows="2" placeholder="Deskripsi / pengantar subdaftar (opsional), misalnya: Berusaha untuk pencapaian SKK 10 macam meliputi..." class="w-full rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800"></textarea>
                                                    <div class="flex flex-wrap items-end gap-2">
                                                        @if ($section !== 'keuangan')
                                                            <label class="text-xs text-zinc-600 dark:text-zinc-300">Jumlah kegiatan
                                                                <input type="number" min="0" wire:key="annual-quantity-{{ $item['key'] ?? $index }}" wire:model="entries.{{ $index }}.quantity" placeholder="–" class="mt-1 block w-24 rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                                            </label>
                                                            <label class="text-xs text-zinc-600 dark:text-zinc-300">Satuan
                                                                <select wire:key="annual-unit-{{ $item['key'] ?? $index }}" wire:model="entries.{{ $index }}.unit" class="mt-1 block rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                                                    <option value="kali">kali</option><option value="buah">buah</option><option value="orang">orang</option><option value="paket">paket</option>
                                                                </select>
                                                            </label>
                                                        @else
                                                            <label class="text-xs text-zinc-600 dark:text-zinc-300">Jenis rencana
                                                                <select wire:key="annual-finance-type-{{ $item['key'] ?? $index }}" wire:model.live="entries.{{ $index }}.finance_type" class="mt-1 block rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                                                    <option value="">Judul / uraian</option><option value="income">Pemasukan</option><option value="expense">Pengeluaran</option><option value="fundraising">Usaha Dana</option>
                                                                </select>
                                                            </label>
                                                            @if (in_array($item['finance_type'] ?? '', ['income','expense'], true))
                                                                <label class="text-xs text-zinc-600 dark:text-zinc-300">Nilai (Rp)
                                                                    <input type="number" min="0" step="1" wire:key="annual-amount-{{ $item['key'] ?? $index }}" wire:model="entries.{{ $index }}.amount" @disabled(! empty($item['rkag_lines'])) placeholder="–" class="mt-1 block w-44 rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                                                    @if (! empty($item['rkag_lines']))
                                                                        <span class="block text-[10px] text-emerald-700">Dihitung otomatis dari rincian saat disimpan.</span>
                                                                    @endif
                                                                </label>
                                                                <span class="rounded-md bg-zinc-200 px-2 py-2 text-xs dark:bg-zinc-700">IDR · Rupiah</span>
                                                                <div class="w-full rounded-lg border border-zinc-300 p-3 dark:border-zinc-700">
                                                                    <div class="flex items-center justify-between gap-2">
                                                                        <span class="text-xs font-semibold">Rincian RKAG — volume × harga satuan</span>
                                                                        <button type="button" wire:click="addRkagLine({{ $index }})" class="rounded border px-2 py-1 text-xs">+ Tambah rincian</button>
                                                                    </div>
                                                                    @foreach (($item['rkag_lines'] ?? []) as $lineIndex => $line)
                                                                        <div class="mt-2 grid gap-2 md:grid-cols-12" wire:key="rkag-{{ $item['key'] ?? $index }}-{{ $lineIndex }}">
                                                                            <label class="text-xs md:col-span-4">Uraian
                                                                                <input wire:model="entries.{{ $index }}.rkag_lines.{{ $lineIndex }}.description" maxlength="200" placeholder="Contoh: Konsumsi Persami" class="mt-1 w-full rounded border p-2 dark:bg-zinc-800">
                                                                            </label>
                                                                            <label class="text-xs md:col-span-2">Volume
                                                                                <input type="number" min="1" max="999999" wire:model="entries.{{ $index }}.rkag_lines.{{ $lineIndex }}.volume" class="mt-1 w-full rounded border p-2 dark:bg-zinc-800">
                                                                            </label>
                                                                            <label class="text-xs md:col-span-2">Satuan
                                                                                <input wire:model="entries.{{ $index }}.rkag_lines.{{ $lineIndex }}.unit" maxlength="30" placeholder="orang" class="mt-1 w-full rounded border p-2 dark:bg-zinc-800">
                                                                            </label>
                                                                            <label class="text-xs md:col-span-3">Harga satuan (Rp)
                                                                                <input type="number" min="0" wire:model="entries.{{ $index }}.rkag_lines.{{ $lineIndex }}.unit_price" class="mt-1 w-full rounded border p-2 dark:bg-zinc-800">
                                                                            </label>
                                                                            <label class="text-xs md:col-span-5">Sumber dana (opsional)
                                                                                <input wire:model="entries.{{ $index }}.rkag_lines.{{ $lineIndex }}.funding_source" maxlength="120" placeholder="Contoh: Iuran anggota / Bantuan Mabigus" class="mt-1 w-full rounded border p-2 dark:bg-zinc-800">
                                                                            </label>
                                                                            <label class="text-xs md:col-span-5">Pagu per rincian (Rp, opsional)
                                                                                <input type="number" min="0" wire:model="entries.{{ $index }}.rkag_lines.{{ $lineIndex }}.ceiling" placeholder="Belum ditetapkan" class="mt-1 w-full rounded border p-2 dark:bg-zinc-800">
                                                                            </label>
                                                                            <div class="self-end text-xs text-zinc-500 md:col-span-2">Pagu bersifat referensi, bukan persetujuan atau posting kas.</div>
                                                                            <button type="button" wire:click="removeRkagLine({{ $index }}, {{ $lineIndex }})" class="self-end rounded border border-red-300 p-2 text-xs text-red-600 md:col-span-1">Hapus</button>
                                                                            @error('entries.'.$index.'.rkag_lines.'.$lineIndex.'.description')<p class="text-xs text-red-600 md:col-span-12">{{ $message }}</p>@enderror
                                                                            @error('entries.'.$index.'.rkag_lines.'.$lineIndex.'.volume')<p class="text-xs text-red-600 md:col-span-12">{{ $message }}</p>@enderror
                                                                            @error('entries.'.$index.'.rkag_lines.'.$lineIndex.'.unit_price')<p class="text-xs text-red-600 md:col-span-12">{{ $message }}</p>@enderror
                                                                            @error('entries.'.$index.'.rkag_lines.'.$lineIndex.'.funding_source')<p class="text-xs text-red-600 md:col-span-12">{{ $message }}</p>@enderror
                                                                            @error('entries.'.$index.'.rkag_lines.'.$lineIndex.'.ceiling')<p class="text-xs text-red-600 md:col-span-12">{{ $message }}</p>@enderror
                                                                        </div>
                                                                    @endforeach
                                                                    @error('entries.'.$index.'.rkag_lines')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                                                                    <p class="mt-2 text-xs text-zinc-500">Jika ada rincian, nominal pada butir ini otomatis dihitung saat Simpan. Tanpa rincian, nominal manual tetap digunakan seperti sebelumnya.</p>
                                                                </div>
                                                                @if ($historicalSuggestions !== [])
                                                                    <div class="w-full">
                                                                        <p class="mb-1 text-xs text-zinc-500">Saran dari buku kas tahun sebelumnya (klik untuk mengisi angka):</p>
                                                                        <div class="flex flex-wrap gap-1">
                                                                            @foreach ($historicalSuggestions as $suggestion)
                                                                                @if ($suggestion['direction'] === ($item['finance_type'] ?? null))
                                                                                    <button type="button" wire:click="useBudgetSuggestion({{ $index }}, {{ $loop->index }})" class="rounded-md border border-dashed border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600">{{ $suggestion['category'] }} · Rp {{ number_format($suggestion['amount'], 0, ',', '.') }}</button>
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
                                <thead class="bg-zinc-50 dark:bg-zinc-800"><tr><th class="p-3">No.</th><th class="min-w-52 p-3">Kegiatan</th>@foreach ($semesterMonthLabels as $monthLabel)<th class="p-2 text-center">{{ $monthLabel }}</th>@endforeach<th class="p-3">Jumlah anggota</th><th class="p-3">Ket.</th><th class="p-3"></th></tr></thead>
                                <tbody>
                                    @foreach ($entries as $index => $row)
                                        <tr wire:key="semester-{{ $row['source_key'] ?? $index }}" class="border-t border-zinc-100 dark:border-zinc-800">
                                            <td class="p-2">{{ $loop->iteration }}.</td>
                                            <td class="p-2">
                                                @if (! empty($row['source_key']))
                                                    <div class="font-medium">{{ $row['description'] }}</div><div class="text-[10px] text-emerald-700">Dari Program Tahunan</div>
                                                @else
                                                    <input wire:model="entries.{{ $index }}.description" class="w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800" aria-label="Kegiatan tambahan semester">
                                                @endif
                                                @error('entries.'.$index.'.description')<span class="block text-red-600">{{ $message }}</span>@enderror
                                            </td>
                                            @foreach ($semesterMonthLabels as $monthIndex => $monthLabel)
                                                <td class="p-2 text-center">
                                                    @if (! empty($row['source_key']))
                                                        <span aria-label="Jadwal bulan {{ $monthLabel }} dari Tahunan">{{ ($row['months'][$monthIndex] ?? false) ? '✓' : '–' }}</span>
                                                    @else
                                                        <input type="checkbox" wire:model="entries.{{ $index }}.months.{{ $monthIndex }}" aria-label="Bulan {{ $monthLabel }} baris {{ $index + 1 }}">
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="p-2"><input type="number" min="0" placeholder="–" wire:model="entries.{{ $index }}.participants" class="w-20 rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></td>
                                            <td class="p-2"><input wire:model="entries.{{ $index }}.notes" class="w-32 rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></td>
                                            <td class="p-2"><button type="button" wire:click="removeRow({{ $index }})" class="text-red-600">Hapus</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </section>
                        <button type="button" wire:click="addRow" class="rounded-lg border border-emerald-600 px-3 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">+ Tambah kegiatan khusus Semester</button>
                    @else
                        <section class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <h3 class="text-center font-bold uppercase">Program Latihan Mingguan Gugusdepan</h3>
                            <p class="mt-2 text-sm text-zinc-500">Susunan kolom mengikuti buku administrasi. Gulir tabel secara horizontal pada layar sempit; hasil cetak menggunakan A4.</p>
                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full min-w-[1100px] border-collapse text-left text-xs">
                                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                                        <tr>
                                            <th rowspan="2" class="border p-2">No</th>
                                            <th rowspan="2" class="border p-2">Waktu</th>
                                            <th rowspan="2" class="border p-2">Kegiatan</th>
                                            <th rowspan="2" class="border p-2">Sasaran</th>
                                            <th colspan="2" class="border p-2 text-center">Pencapaian</th>
                                            <th rowspan="2" class="border p-2">Alat</th>
                                            <th rowspan="2" class="border p-2">Petugas</th>
                                            <th rowspan="2" class="border p-2">Acara Selingan</th>
                                            <th rowspan="2" class="border p-2">KET</th>
                                            <th rowspan="2" class="border p-2">Aksi</th>
                                        </tr>
                                        <tr><th class="border p-2">SKU</th><th class="border p-2">SKK</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($entries as $index => $row)
                                            <tr wire:key="weekly-{{ $row['key'] ?? $index }}">
                                                <td class="border p-2">{{ $loop->iteration }}</td>
                                                @foreach ([
                                                    'time' => 'Waktu (mis. 15.00–15.15)',
                                                    'description' => 'Kegiatan',
                                                    'target' => 'Sasaran',
                                                    'sku' => 'SKU',
                                                    'skk' => 'SKK',
                                                    'equipment' => 'Alat',
                                                    'officer' => 'Petugas',
                                                    'interlude' => 'Acara selingan',
                                                    'notes' => 'Keterangan',
                                                ] as $field => $placeholder)
                                                    <td class="border p-1 align-top">
                                                        <input wire:model="entries.{{ $index }}.{{ $field }}" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }} baris {{ $index + 1 }}" class="w-full min-w-24 rounded-md border border-zinc-300 p-2 dark:bg-zinc-800">
                                                    </td>
                                                @endforeach
                                                <td class="border p-2">
                                                    <button type="button" wire:click="removeRow({{ $index }})" class="text-red-600">Hapus</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" wire:click="addRow" class="mt-3 rounded-lg border border-emerald-600 px-3 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">+ Tambah segmen latihan</button>
                        </section>
                        <section class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <h3 class="font-semibold">Penandatangan dan persetujuan</h3>
                            <p class="mt-1 text-xs text-zinc-500">Menggunakan data pengurus dan anggota yang tercatat dalam SIMPRAM. Tidak membuat data personel baru.</p>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <label class="text-sm font-medium">Mengetahui — Pembina Gugus Depan
                                    <select wire:model="acknowledgedByRef" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                                        <option value="">Pilih pembina/pengurus Gudep</option>
                                        @foreach ($weeklyAcknowledgers as $option)
                                            <option value="{{ $option['ref'] }}">{{ $option['name'] }} — {{ $option['position'] }} ({{ $option['scope'] }})</option>
                                        @endforeach
                                    </select>
                                    @error('acknowledgedByRef')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                                </label>
                                <label class="text-sm font-medium">Penandatangan — {{ \App\Support\GudepWeeklyPersonnel::signerRole($levels->firstWhere('id', $scoutLevelId)?->name) }}
                                    <select wire:model="signedByRef" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                                        <option value="">Pilih sesuai jabatan satuan</option>
                                        @foreach ($weeklySigners as $option)
                                            <option value="{{ $option['ref'] }}">{{ $option['name'] }} — {{ $option['position'] }} ({{ $option['scope'] }})</option>
                                        @endforeach
                                    </select>
                                    @error('signedByRef')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                                </label>
                            </div>
                            <p class="mt-3 text-xs text-zinc-500">Siaga: Pembina · Penggalang: Pratama · Penegak: Pradana · Pandega: Ketua Racana. Dokumen kerja boleh disimpan tanpa penandatangan, tetapi PDF resmi memerlukan keduanya.</p>
                            @if ($weeklySigners === [])
                                <p class="mt-2 text-xs text-amber-700">Belum ada pengurus dengan jabatan yang sesuai pada formasi dan periode ini. Lengkapi kepengurusan atau posisi anggota muda terlebih dahulu.</p>
                            @endif
                        </section>
                    @endif
                    @error('entries')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    @if ($kind === 'annual' || $kind === 'semester')
                        <section class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <h3 class="font-semibold">Pengesahan Program Kerja</h3>
                            <p class="mt-1 text-xs text-zinc-500">Penandatangan wajib berasal dari daftar pengurus SIMPRAM dan harus disimpan sebelum menerbitkan PDF.</p>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <label class="text-sm font-medium">Mengetahui — Ketua Mabigus
                                    <select wire:model="acknowledgedByRef" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                                        <option value="">Pilih Ketua Mabigus</option>
                                        @foreach ($annualSemesterKamabigus as $option)
                                            <option value="{{ $option['ref'] }}">{{ $option['name'] }} — {{ $option['position'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('acknowledgedByRef')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                                </label>
                                <label class="text-sm font-medium">Penandatangan — Ketua/Pembina Gugusdepan
                                    <select wire:model="signedByRef" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                                        <option value="">Pilih Ketua/Pembina Gudep</option>
                                        @foreach ($annualSemesterPembina as $option)
                                            <option value="{{ $option['ref'] }}">{{ $option['name'] }} — {{ $option['position'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('signedByRef')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                                </label>
                            </div>
                        </section>
                    @endif
                    <section class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold">Tembusan dokumen</h3>
                            <button type="button" wire:click="addCopyRecipient" class="rounded border px-3 py-1 text-xs">+ Tembusan</button>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">Opsional, maksimal 4 penerima. Ditampilkan pada bagian bawah halaman terakhir PDF resmi, sebelum catatan arsip dan QR verifikasi.</p>
                        <div class="mt-2 space-y-2">
                            @foreach ($ccRecipients as $index => $recipient)
                                <div class="flex items-center gap-2" wire:key="program-cc-{{ count($ccRecipients) }}-{{ $index }}">
                                    <span class="text-xs">{{ $index + 1 }}.</span>
                                    <input wire:model="ccRecipients.{{ $index }}" maxlength="180" placeholder="Contoh: Ketua Kwartir Ranting" class="w-full rounded-lg border border-zinc-300 p-2 text-sm dark:bg-zinc-800">
                                    <button type="button" wire:click="removeCopyRecipient({{ $index }})" class="text-xs text-red-600">Hapus</button>
                                </div>
                            @endforeach
                        </div>
                    </section>
                    <label class="block text-sm font-medium">Catatan tambahan
                        <textarea wire:model="notes" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></textarea>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white">
                            <span wire:loading.remove wire:target="save">Simpan {{ $editingId ? 'Perubahan' : 'Program Kerja' }}</span>
                            <span wire:loading wire:target="save">Menyimpan…</span>
                        </button>
                        @if ($editingId)<button type="button" wire:click="resetDraft" class="rounded-lg border border-zinc-300 px-4 py-2">Batal edit</button>@endif
                    </div>
                </form>
            @else
                <p class="mt-4 text-sm text-zinc-500">Akun ini memiliki akses lihat saja. Perubahan memerlukan izin pengelolaan pembinaan Gudep.</p>
            @endif
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" aria-label="Realisasi program Gudep">
            <h2 class="text-lg font-bold">Rencana dan Pelaksanaan Program</h2>
            <p class="mt-1 text-sm text-zinc-500">Dihitung dari program serta kegiatan dan jurnal asli yang tertaut. Rencana tidak otomatis dianggap terlaksana.</p>
            <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-6">
                @foreach ([
                    ['Tahunan', $programProgress['annual']],
                    ['Semester', $programProgress['semester']],
                    ['Mingguan', $programProgress['weekly']],
                    ['Tertaut kegiatan', $programProgress['linked']],
                    ['Agenda selesai', $programProgress['completed']],
                    ['Selesai + jurnal', $programProgress['evidenced']],
                ] as [$label, $value])
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
                        <p class="text-xs text-zinc-500">{{ $label }}</p>
                        <p class="mt-1 text-xl font-bold tabular-nums">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-bold">Dashboard Daftar Program</h2>
            @error('publication')
                <div role="alert" class="mt-3 rounded-lg border border-red-400 bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">{{ $message }}</div>
            @enderror
            <p class="mt-1 text-sm text-zinc-500">Ringkasan program tersimpan berdasarkan Gudep dan tahun ajaran, tanpa membuat ulang data anggota/satuan.</p>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead><tr class="border-b text-zinc-500"><th class="p-2">Jenis</th><th class="p-2">Program</th><th class="p-2">Golongan</th><th class="p-2">Jumlah baris</th><th class="p-2">Agenda terhubung</th><th class="p-2">Status pelaksanaan</th><th class="p-2">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($sheets as $sheet)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="p-2">{{ ['annual' => 'Tahunan', 'semester' => 'Semester', 'weekly' => 'Mingguan'][$sheet->kind] ?? $sheet->kind }}</td>
                                <td class="p-2 font-medium">{{ $sheet->title }}
                                    <span class="block text-xs font-normal text-zinc-500">{{ $sheet->scheduled_for?->format('d/m/Y') ?? '–' }}</span>
                                    @if ($sheet->parent)
                                        <span class="block text-xs font-normal text-zinc-500">Induk: {{ $sheet->parent->title }}</span>
                                    @endif
                                </td>
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
                                <td class="p-2 text-xs text-zinc-600 dark:text-zinc-300">{{ \App\Support\GudepProgramProgress::evidenceLabel($sheet) }}</td>
                                <td class="p-2">
                                    <button type="button" wire:click="preview({{ $sheet->id }})" class="text-emerald-700 underline dark:text-emerald-300">Lihat</button>
                                    @if (auth()->user()?->can('reports.export') && (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage')))
                                        <button type="button" wire:click="publishProgram({{ $sheet->id }})" wire:confirm="Terbitkan PDF resmi Program Kerja ini? Perubahan isi berikutnya akan menjadi revisi dokumen." class="ml-2 font-semibold text-emerald-700 underline dark:text-emerald-300">Terbitkan PDF + QR</button>
                                    @endif
                                    @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage'))
                                        <button type="button" wire:click="edit({{ $sheet->id }})" class="ml-2 text-emerald-700 underline dark:text-emerald-300">Edit</button>
                                        @if (in_array($sheet->kind, ['annual', 'semester'], true))
                                            <button type="button" wire:click="prepareChild({{ $sheet->id }})" class="ml-2 font-semibold text-emerald-700 underline dark:text-emerald-300">
                                                {{ $sheet->kind === 'annual' ? 'Susun Semester' : 'Susun Mingguan' }}
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-4 text-center text-zinc-500">Belum ada program kerja.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @if ($previewSheet && $previewSheet->kind === 'annual' && $rkagApprovalState)
                <section class="mt-5 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" aria-label="Persetujuan RKAG">
                    <h3 class="font-semibold">Persetujuan Rencana Kegiatan dan Anggaran Gugusdepan</h3>
                    <p class="mt-1 text-sm">Status: <strong>{{ ['draft' => 'Draft', 'submitted' => 'Diajukan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'stale' => 'Perlu pengajuan ulang setelah revisi RKAG'][$rkagApprovalState['status']] ?? 'Draft' }}</strong></p>
                    <p class="mt-1 text-xs text-zinc-500">Keputusan berlaku pada isi keuangan yang diajukan. Jika RKAG diedit, persetujuan sebelumnya tidak berlaku untuk versi yang baru. Pengaju dan penyetuju harus berbeda. Tidak ada transaksi kas atau anggaran resmi yang diposting secara otomatis.</p>
                    @if ($rkagApprovalNotice !== '')<p role="status" class="mt-2 text-sm text-emerald-700">{{ $rkagApprovalNotice }}</p>@endif
                    @error('rkagApproval')<p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    @error('rkagApprovalNotes')<p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    <label class="mt-3 block text-sm">Catatan atau alasan penolakan
                        <textarea wire:model="rkagApprovalNotes" maxlength="1000" rows="2" class="mt-1 block w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800" placeholder="Keterangan pengajuan/keputusan; wajib untuk penolakan"></textarea>
                    </label>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if (in_array($rkagApprovalState['status'], ['draft', 'rejected', 'stale'], true) && (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.finance.manage')))
                            <button type="button" wire:click="submitRkag({{ $previewSheet->id }})" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white">Ajukan RKAG</button>
                        @endif
                        @if ($rkagApprovalState['status'] === 'submitted' && auth()->user()?->can('gudep.finance.manage') && (int) ($rkagApprovalState['last']?->actor_id ?? 0) !== (int) auth()->id())
                            <button type="button" wire:click="approveRkag({{ $previewSheet->id }})" class="rounded-lg border border-emerald-700 px-3 py-2 text-sm">Setujui RKAG</button>
                            <button type="button" wire:click="rejectRkag({{ $previewSheet->id }})" class="rounded-lg border border-red-600 px-3 py-2 text-sm text-red-600">Tolak RKAG</button>
                        @endif
                    </div>
                    @if ($rkagApprovalHistory->isNotEmpty())
                        <h4 class="mt-4 text-sm font-semibold">Riwayat keputusan</h4>
                        <div class="mt-2 overflow-x-auto">
                            <table class="min-w-full text-left text-xs">
                                <thead><tr class="border-b"><th class="p-2">Tanggal</th><th class="p-2">Aksi</th><th class="p-2">Pengurus</th><th class="p-2">Catatan</th></tr></thead>
                                <tbody>
                                    @foreach ($rkagApprovalHistory as $decision)
                                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                            <td class="p-2">{{ $decision->created_at?->format('d/m/Y H:i') }}</td>
                                            <td class="p-2">{{ ['submit' => 'Diajukan', 'approve' => 'Disetujui', 'reject' => 'Ditolak'][$decision->action] ?? $decision->action }}</td>
                                            <td class="p-2">{{ $decision->actor?->name ?? 'Pengurus tidak tersedia' }}</td>
                                            <td class="p-2">{{ $decision->notes ?: '–' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endif
            @if ($previewSheet && $previewSheet->kind === 'annual' && ($rkagActualComparison ?? []) !== [])
                <section class="mt-5 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" aria-label="Rekonsiliasi RKAG berdasarkan kategori">
                    <h3 class="font-semibold">Perbandingan RKAG dan realisasi buku kas</h3>
                    <p class="mt-1 text-xs text-zinc-500">Hanya memakai transaksi kas yang sudah diposting pada tahun ajaran ini dan kategori yang namanya cocok persis (tanpa membedakan huruf besar-kecil). Ini perbandingan tingkat kategori, bukan bukti bahwa transaksi tertentu berasal dari rincian RKAG.</p>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead><tr class="border-b text-zinc-500"><th class="p-2">Jenis / kategori</th><th class="p-2 text-right">Rencana</th><th class="p-2 text-right">Realisasi terposting</th><th class="p-2 text-right">Sisa rencana</th><th class="p-2">Pencocokan</th></tr></thead>
                            <tbody>
                                @foreach ($rkagActualComparison as $comparison)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                        <td class="p-2">{{ $comparison['direction'] === 'expense' ? 'Pengeluaran' : 'Pemasukan' }} · {{ $comparison['category'] }}</td>
                                        <td class="p-2 text-right">Rp {{ number_format($comparison['planned'], 0, ',', '.') }}</td>
                                        <td class="p-2 text-right">Rp {{ number_format($comparison['actual'], 0, ',', '.') }}</td>
                                        <td class="p-2 text-right">Rp {{ number_format($comparison['variance'], 0, ',', '.') }}</td>
                                        <td class="p-2">{{ $comparison['matched'] ? 'Kategori cocok' : 'Belum ada kategori kas cocok' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
            @if ($previewSheet)
                <div class="mt-5 space-y-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" aria-label="Pratinjau dokumen program">
                    @if ($previewLetterhead)
                        @include('administration.partials.gudep-document-letterhead', ['letterhead' => $previewLetterhead])
                    @endif
                    @if ($previewSheet->kind === 'semester')
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
                        @include($previewSheet->document_layout === 'monthly_matrix' ? 'administration.partials.annual-program-matrix' : 'administration.partials.annual-program-body', [
                            'entries' => $previewSheet->entries ?? [],
                            'kwarcab' => $gudep->kwarcab,
                            'maleNumber' => $gudep->male_number,
                            'femaleNumber' => $gudep->female_number,
                            'yearName' => $year->name,
                            'formationName' => $previewAnnualFormationName ?? null,
                            'programNumber' => $previewSheet->letterhead_type === 'female' ? ($previewLetterhead['female_number'] ?? '–') : ($previewSheet->letterhead_type === 'male' ? ($previewLetterhead['male_number'] ?? '–') : (($previewLetterhead['male_number'] ?? '–').'-'.($previewLetterhead['female_number'] ?? '–'))),
                        ])
                    @elseif ($previewSheet->kind === 'weekly')
                        @include('administration.partials.weekly-program-body', [
                            'weeklyRows' => $previewSheet->entries ?? [],
                            'weeklySignatures' => $previewSignatures ?? [],
                            'weeklyDate' => $previewSheet->scheduled_for?->format('d/m/Y'),
                        ])
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-xs">
                                <thead><tr class="border-b">
                                    <th class="p-2">No.</th><th class="p-2">Kegiatan</th>
                                    @if ($previewSheet->kind === 'semester')
                                        @foreach (\App\Support\GudepProgramSemesterProjection::monthLabels($previewSheet->semester) as $month)<th class="p-2">{{ $month }}</th>@endforeach
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
                                                @foreach (\App\Support\GudepProgramSemesterProjection::monthLabels($previewSheet->semester) as $monthIndex => $month)<td class="p-2">{{ ($entry['months'][$monthIndex] ?? false) ? '✓' : '–' }}</td>@endforeach
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
                    @if ($previewSheet->kind !== 'weekly')
                        @include('administration.partials.program-signatures', [
                            'programSignatures' => $previewSignatures ?? [],
                            'programDate' => '........................',
                            'programCity' => $signingCity ?? '',
                            'programNumber' => $previewSheet->letterhead_type === 'female'
                                ? ($previewLetterhead['female_number'] ?? '–')
                                : ($previewSheet->letterhead_type === 'male'
                                    ? ($previewLetterhead['male_number'] ?? '–')
                                    : (($previewLetterhead['male_number'] ?? '–').'-'.($previewLetterhead['female_number'] ?? '–'))),
                        ])
                    @endif
                </div>
            @endif
            </div>
        </section>
    @endif
</div>
