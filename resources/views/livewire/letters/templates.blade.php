<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Template Surat</h1>
        <p class="mt-1 text-sm text-zinc-500">Pilih template, susun isi, lalu tambahkan variabel tanpa perlu menghafal kode placeholder.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-5">
        <div class="space-y-2 lg:col-span-2">
            @foreach ($templates as $template)
                <button type="button" wire:click="edit({{ $template->id }})" class="w-full rounded-xl border border-zinc-200 bg-white p-4 text-left hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                    <div class="font-medium">{{ $template->name }}</div>
                    <div class="mt-1 text-xs text-zinc-500">{{ $template->letterType?->code }} · {{ $template->default_field_code ?: '-' }} · {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</div>
                </button>
            @endforeach
        </div>

        <div class="lg:col-span-3">
            @if ($editingId)
                <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div><label class="mb-1 block text-sm font-medium">Nama</label><input wire:model="name" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
                        <div><label class="mb-1 block text-sm font-medium">Judul Dokumen</label><input wire:model="title" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
                        <div><label class="mb-1 block text-sm font-medium">Jenis Surat</label><select wire:model="letter_type_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"><option value="">-</option>@foreach ($types as $type)<option value="{{ $type->id }}">{{ $type->code }} - {{ $type->name }}</option>@endforeach</select></div>
                        <div><label class="mb-1 block text-sm font-medium">Bidang Default</label><input wire:model="default_field_code" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" placeholder="A / B / C"></div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label class="block text-sm font-medium">Isi Template</label>
                            <span class="text-xs text-zinc-500">Variabel ditulis dalam bentuk @{{ nama_variabel }}</span>
                        </div>
                        <textarea wire:model.live.debounce.400ms="body_template" rows="16" class="w-full rounded-lg border border-zinc-300 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-800"></textarea>
                        @error('body_template')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="mb-3">
                            <h3 class="text-sm font-semibold">Tambah Variabel</h3>
                            <p class="text-xs text-zinc-500">Klik variabel untuk menambahkannya ke akhir isi template. Anda dapat memindahkan tokennya ke posisi yang diinginkan setelah itu.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($placeholderCatalog as $key => $label)
                                <button type="button" wire:click="insertPlaceholder('{{ $key }}')" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">+ {{ $label }}</button>
                            @endforeach
                        </div>
                    </div>

                    @if ($placeholders)
                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-200">
                            <div class="mb-2 font-medium">Variabel yang dipakai template ini:</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($placeholders as $placeholder)
                                    <code class="rounded bg-white/80 px-2 py-1 dark:bg-zinc-900">@{{ {{ $placeholder }} }}</code>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active"> Template aktif</label>
                    <div class="flex gap-2"><button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm text-white dark:bg-white dark:text-zinc-900">Simpan</button><button type="button" wire:click="cancel" class="rounded-lg border px-4 py-2 text-sm">Batal</button></div>
                </form>
            @else
                <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center text-sm text-zinc-500 dark:border-zinc-700">Pilih template di sebelah kiri untuk membuka Template Builder.</div>
            @endif
        </div>
    </div>
</div>
