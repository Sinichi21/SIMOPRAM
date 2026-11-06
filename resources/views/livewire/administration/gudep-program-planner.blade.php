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
                        @foreach (\App\Livewire\Administration\GudepProgramPlanner::annualSections() as $section => $heading)
                            @php $sectionRows = collect($entries)->filter(fn ($row) => ($row['section'] ?? null) === $section); @endphp
                            <section class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                                <div class="flex flex-wrap items-center justify-between gap-2 bg-zinc-50 px-4 py-3 dark:bg-zinc-800">
                                    <h3 class="font-semibold">{{ $heading }}</h3>
                                    <button type="button" wire:click="addRow('{{ $section }}')" class="rounded-lg border border-emerald-600 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300">+ Tambah baris</button>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-left text-sm">
                                        <thead><tr class="border-b text-zinc-500"><th class="w-12 p-3">No.</th><th class="p-3">Uraian kegiatan / SKK / kebutuhan</th><th class="w-32 p-3">Jumlah</th><th class="w-20 p-3">Aksi</th></tr></thead>
                                        <tbody>
                                            @forelse ($sectionRows as $index => $row)
                                                <tr wire:key="annual-{{ $index }}" class="border-b border-zinc-100 dark:border-zinc-800">
                                                    <td class="p-3 align-top">{{ $loop->iteration }}.</td>
                                                    <td class="p-2"><input wire:model="entries.{{ $index }}.description" maxlength="500" placeholder="Tulis kegiatan / SKK / perlengkapan" class="w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">@error('entries.'.$index.'.description')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</td>
                                                    <td class="p-2"><input type="number" min="0" wire:model="entries.{{ $index }}.quantity" placeholder="–" class="w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">@error('entries.'.$index.'.quantity')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</td>
                                                    <td class="p-2"><button type="button" wire:click="removeRow({{ $index }})" class="text-xs font-medium text-red-600">Hapus</button></td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="p-3 text-xs text-zinc-500">Belum ada kegiatan untuk bagian ini. Jika golongan tidak tersedia, bagian dapat dibiarkan kosong.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
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
                                            @for ($month=0; $month<6; $month++)<td class="p-2 text-center"><input type="checkbox" wire:model="entries.{{ $index }}.months.{{ $month }}" aria-label="Bulan {{ $month+1 }} baris {{ $loop->parent->iteration }}"></td>@endfor
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
                    <thead><tr class="border-b text-zinc-500"><th class="p-2">Jenis</th><th class="p-2">Program</th><th class="p-2">Golongan</th><th class="p-2">Jumlah baris</th><th class="p-2">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($sheets as $sheet)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="p-2">{{ ['annual' => 'Tahunan', 'semester' => 'Semester', 'weekly' => 'Mingguan'][$sheet->kind] ?? $sheet->kind }}</td>
                                <td class="p-2 font-medium">{{ $sheet->title }} <span class="block text-xs font-normal text-zinc-500">{{ $sheet->scheduled_for?->format('d/m/Y') ?? '–' }}</span></td>
                                <td class="p-2">{{ $levels->firstWhere('id', $sheet->scout_level_id)?->name ?? 'Seluruh Gudep / –' }}</td>
                                <td class="p-2">{{ count($sheet->entries ?? []) }}</td>
                                <td class="p-2">@if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage'))<button type="button" wire:click="edit({{ $sheet->id }})" class="text-emerald-700 underline dark:text-emerald-300">Buka/Edit</button>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-center text-zinc-500">Belum ada program kerja.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
