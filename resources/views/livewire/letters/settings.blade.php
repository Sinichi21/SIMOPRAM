<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Pengaturan Persuratan</h1>
        <p class="mt-1 text-sm text-zinc-500">
            Halaman ini khusus format nomor dan sequence. Identitas dokumen, Gudep, kota, kop,
            dan penandatangan dikelola di Pengaturan Dokumen Sekolah.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-900 dark:bg-blue-950/20">
        <div class="font-semibold">Pengaturan dokumen telah disatukan</div>
        <p class="mt-1 text-sm">
            Penandatangan, jabatan, NIP/NTA, nomor Gudep, kota dan identitas dokumen tidak lagi diedit di halaman Persuratan.
        </p>
        @if (Route::has('settings.school-documents'))
            <a href="{{ route('settings.school-documents') }}" wire:navigate class="mt-3 inline-flex rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white">
                Buka Pengaturan Dokumen
            </a>
        @endif
    </div>

    <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <label class="mb-1 block text-sm font-medium">Format Nomor Surat *</label>
            <input wire:model="number_format" class="w-full rounded-lg border border-zinc-300 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="mt-1 text-xs text-zinc-500">
                Token: {sequence}, {sequence_padded}, {type}, {month_roman}, {year}, {year_short}, {gudep}, {field}.
                Token {gudep} otomatis memakai Pengaturan Dokumen.
            </p>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Format Nomor Agenda Surat Masuk *</label>
            <input wire:model="agenda_format" class="w-full rounded-lg border border-zinc-300 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-800">
        </div>
        <button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">Simpan Format Nomor</button>
    </form>

    <form wire:submit="saveSequence" class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/20">
        <h2 class="font-semibold">Sinkronisasi Nomor Terakhir</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm font-medium">Tahun</label><input type="number" wire:model="sequenceYear" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
            <div><label class="mb-1 block text-sm font-medium">Nomor terakhir</label><input type="number" wire:model="lastOutgoingNumber" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
            <div class="flex items-end"><button class="w-full rounded-lg bg-amber-700 px-4 py-2 text-sm font-medium text-white">Set Sequence</button></div>
        </div>
    </form>

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Jenis Surat</h2>
            <div class="mt-3 space-y-2 text-sm">@foreach ($types as $type)<div class="flex gap-3"><span class="w-8 font-mono font-semibold">{{ $type->code }}</span><span>{{ $type->name }}</span></div>@endforeach</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Bidang Surat</h2>
            <div class="mt-3 space-y-2 text-sm">@foreach ($fields as $field)<div class="flex gap-3"><span class="w-8 font-mono font-semibold">{{ $field->code }}</span><span>{{ $field->name }}</span></div>@endforeach</div>
        </div>
    </div>
</div>
