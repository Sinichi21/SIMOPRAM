<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Pengaturan Persuratan</h1>
        <p class="mt-1 text-sm text-zinc-500">Atur nomor surat, identitas gugusdepan, kop, dan sequence per sekolah.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Kode Gugusdepan *</label>
                <input wire:model="gudep_code" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" placeholder="04.007-008">
                @error('gudep_code') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Kota</label>
                <input wire:model="city" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" placeholder="Denpasar">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Format Nomor Surat *</label>
                <input wire:model="number_format" class="w-full rounded-lg border border-zinc-300 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <p class="mt-1 text-xs text-zinc-500">Token: {sequence}, {sequence_padded}, {type}, {month_roman}, {year}, {year_short}, {gudep}, {field}</p>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Format Nomor Agenda Surat Masuk *</label>
                <input wire:model="agenda_format" class="w-full rounded-lg border border-zinc-300 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-800">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Judul Kop</label>
                <input wire:model="letterhead_title" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Subjudul Kop</label>
                <input wire:model="letterhead_subtitle" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Alamat Kop</label>
                <textarea wire:model="letterhead_address" rows="2" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></textarea>
            </div>
            <div><label class="mb-1 block text-sm font-medium">Penandatangan Default</label><input wire:model="default_signatory_name" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
            <div><label class="mb-1 block text-sm font-medium">Jabatan</label><input wire:model="default_signatory_position" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
            <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium">NTA / Identitas Penandatangan</label><input wire:model="default_signatory_identity" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
        </div>
        <button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">Simpan Pengaturan</button>
    </form>

    <form wire:submit="saveSequence" class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/20">
        <h2 class="font-semibold">Sinkronisasi Nomor Terakhir</h2>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Gunakan ini saat memindahkan pencatatan lama dari spreadsheet agar nomor berikutnya tidak mengulang.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm font-medium">Tahun</label><input type="number" wire:model="sequenceYear" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
            <div><label class="mb-1 block text-sm font-medium">Nomor terakhir</label><input type="number" wire:model="lastOutgoingNumber" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
            <div class="flex items-end"><button class="w-full rounded-lg bg-amber-700 px-4 py-2 text-sm font-medium text-white">Set Sequence</button></div>
        </div>
    </form>

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Jenis Surat</h2>
            <div class="mt-3 space-y-2 text-sm">
                @foreach ($types as $type)<div class="flex gap-3"><span class="w-8 font-mono font-semibold">{{ $type->code }}</span><span>{{ $type->name }}</span></div>@endforeach
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Bidang Surat</h2>
            <div class="mt-3 space-y-2 text-sm">
                @foreach ($fields as $field)<div class="flex gap-3"><span class="w-8 font-mono font-semibold">{{ $field->code }}</span><span>{{ $field->name }}</span></div>@endforeach
            </div>
        </div>
    </div>
</div>
