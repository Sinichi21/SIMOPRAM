<section
    class="rounded-xl border border-zinc-200
           bg-white p-6 shadow-sm
           dark:border-zinc-800
           dark:bg-zinc-900"
>
    <h2 class="text-lg font-semibold">
        Rentang Predikat dan Saran Deskripsi
    </h2>
    <p class="mt-1 text-sm text-zinc-500">
        Buat konfigurasi untuk setiap golongan, lalu atur rentang nilai 0-100 hingga dua angka desimal.
        Deskripsi menjadi saran sesuai predikat dan dapat
        disesuaikan untuk setiap siswa di menu Penilaian.
    </p>

    @if (session('grade-ranges'))
        <flux:callout class="mt-4">{{ session('grade-ranges') }}</flux:callout>
    @endif

    <form wire:submit="createConfig" class="mt-6 grid items-end gap-4 md:grid-cols-3">
        <flux:select wire:model="scoutLevelId" label="Golongan">
            <option value="">Pilih golongan</option>
            @foreach ($scoutLevels as $level)
                <option value="{{ $level->id }}" wire:key="range-level-{{ $level->id }}">{{ $level->name }}</option>
            @endforeach
        </flux:select>
        <flux:input wire:model="configName" label="Nama konfigurasi" placeholder="Contoh: Deskripsi Siaga" />
        <flux:button type="submit" variant="primary">Buat konfigurasi</flux:button>
    </form>

    <flux:error name="ranges" />
    <div class="mt-6 space-y-3">
        @forelse ($configs as $config)
            <div wire:key="range-config-{{ $config->id }}" class="flex flex-col justify-between gap-3 rounded-lg border border-zinc-200 p-4 md:flex-row md:items-center dark:border-zinc-700">
                <div>
                    <p class="font-semibold">{{ $config->name }}</p>
                    <p class="text-sm text-zinc-500">{{ $config->scoutLevel?->name ?? 'Umum (konfigurasi lama)' }} · {{ $config->scales_count }} rentang · {{ $config->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button wire:click="editConfig({{ $config->id }})" data-action-tone="edit">Edit</flux:button>
                    <flux:button wire:click="toggleConfig({{ $config->id }})" wire:confirm="Ubah status konfigurasi? Hanya satu konfigurasi aktif per golongan." data-action-tone="status">{{ $config->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</flux:button>
                    <flux:button wire:click="deleteConfig({{ $config->id }})" wire:confirm="Hapus konfigurasi ini? Nilai yang sudah tersimpan tetap dipertahankan." data-action-tone="danger">Hapus</flux:button>
                </div>
            </div>
        @empty
            <p class="rounded-lg border border-dashed p-6 text-center text-sm text-zinc-500">Belum ada konfigurasi rentang dan deskripsi. Pilih golongan untuk membuat konfigurasi.</p>
        @endforelse
    </div>
    <p class="mt-4 text-sm text-zinc-500">Konfigurasi aktif sesuai golongan siswa pada tahun ajaran penilaian digunakan terlebih dahulu. Konfigurasi umum lama menjadi cadangan jika belum ada konfigurasi aktif untuk golongan tersebut. Hitung ulang nilai setelah mengubah konfigurasi; deskripsi manual siswa tetap dipertahankan.</p>

    @if ($editingConfig)
    <form wire:submit="save" class="mt-6 space-y-6" wire:key="edit-ranges-{{ $editingConfig->id }}">
        <h3 class="text-lg font-semibold">{{ $editingConfig->name }} — {{ $editingConfig->scoutLevel?->name ?? 'Umum' }}</h3>
        <flux:error name="ranges" />
        @foreach ($ranges as $index => $range)
            <fieldset
                wire:key="grade-range-{{ $index }}"
                class="min-w-0 space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700"
            >
                <legend class="px-2 text-sm font-semibold">Rentang {{ $index + 1 }}</legend>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input wire:model="ranges.{{ $index }}.letter_grade" label="Predikat" />
                    <flux:input wire:model="ranges.{{ $index }}.min_score" label="Nilai minimum" type="number" step="0.01" />
                    <flux:input wire:model="ranges.{{ $index }}.max_score" label="Nilai maksimum" type="number" step="0.01" />
                </div>

                <flux:textarea wire:model="ranges.{{ $index }}.description" label="Saran deskripsi" rows="2" />

                <div class="flex justify-end">
                    <flux:button type="button" wire:click="removeRange({{ $index }})">Hapus rentang</flux:button>
                </div>
            </fieldset>
        @endforeach
        <div class="flex flex-col gap-2 border-t border-zinc-200 pt-4 sm:flex-row sm:flex-wrap dark:border-zinc-800">
            <flux:button type="button" wire:click="addRange">Tambah rentang</flux:button>
            <flux:button type="submit" variant="primary">Simpan Rentang dan Deskripsi</flux:button>
            <flux:button type="button" wire:click="cancelEdit">Tutup</flux:button>
        </div>
    </form>
    @endif
</section>
