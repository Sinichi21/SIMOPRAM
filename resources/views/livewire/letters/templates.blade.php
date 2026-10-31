<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Template Surat</h1>
        <p class="mt-1 text-sm text-zinc-500">Template mengatur layout dokumen setelah kop: posisi nomor, lampiran, perihal, tujuan, isi, tabel, hingga blok tanda tangan. Footer QR tetap dikelola sistem.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-5">
        <div class="space-y-2 lg:col-span-2">
            <button type="button" wire:click="createNew" class="mb-3 w-full rounded-xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">+ Template Baru</button>
            @foreach ($templates as $template)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <button type="button" wire:click="edit({{ $template->id }})" class="w-full text-left">
                        <div class="font-medium">{{ $template->name }}</div>
                        <div class="mt-1 text-xs text-zinc-500">{{ $template->letterType?->code }} · {{ $template->default_field_code ?: '-' }} · {{ $template->requires_recipient ? 'Tujuan wajib' : 'Tujuan opsional' }} · {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</div>
                    </button>
                    <div class="mt-3 flex gap-3 border-t border-zinc-100 pt-3 text-xs dark:border-zinc-800">
                        <button type="button" wire:click="duplicate({{ $template->id }})" class="text-blue-600">Duplikat</button>
                        <button type="button" wire:click="delete({{ $template->id }})" wire:confirm="Hapus template ini? Template yang sudah pernah dipakai tidak dapat dihapus." class="text-red-600">Hapus</button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="lg:col-span-3">
            @if ($editingId || $creating)
                <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div><label class="mb-1 block text-sm font-medium">Nama</label><input wire:model="name" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
                        <div><label class="mb-1 block text-sm font-medium">Judul Dokumen</label><input wire:model="title" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>
                        <div><label class="mb-1 block text-sm font-medium">Jenis Surat</label><select wire:model="letter_type_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"><option value="">-</option>@foreach ($types as $type)<option value="{{ $type->id }}">{{ $type->code }} - {{ $type->name }}</option>@endforeach</select></div>
                        <div><label class="mb-1 block text-sm font-medium">Bidang Default</label><input wire:model="default_field_code" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" placeholder="A / B / C"></div>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                        <div class="text-sm font-semibold">Layout awal</div>
                        <p class="mt-1 text-xs text-zinc-500">Opsional. Tombol ini mengganti isi editor, jadi gunakan terutama saat membuat template baru.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" wire:click="applyStarter('general')" class="rounded-lg border bg-white px-3 py-1.5 text-xs dark:bg-zinc-900">Surat Umum</button>
                            <button type="button" wire:click="applyStarter('decision')" class="rounded-lg border bg-white px-3 py-1.5 text-xs dark:bg-zinc-900">Keputusan / SK</button>
                            <button type="button" wire:click="applyStarter('assignment')" class="rounded-lg border bg-white px-3 py-1.5 text-xs dark:bg-zinc-900">Surat Tugas</button>
                            <button type="button" wire:click="applyStarter('blank')" class="rounded-lg border bg-white px-3 py-1.5 text-xs dark:bg-zinc-900">Kosong</button>
                        </div>
                    </div>

                    <div
                        wire:key="rich-template-editor-{{ $editingId ?: 'new' }}"
                        x-data="outgoingLetterEditor(@js($body_template), 'body_template')"
                        class="space-y-2"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <label class="block text-sm font-medium">Isi Template</label>
                                <p class="mt-1 text-xs text-zinc-500">Times New Roman 12 pt. Hanya kop resmi dan footer QR yang tetap dikelola sistem; bagian lainnya dapat disusun di template.</p>
                            </div>
                            <span class="text-xs text-zinc-500">Variabel: @{{ nama_variabel }}</span>
                        </div>

                        <div wire:ignore class="letter-ckeditor">
                            <div x-ref="editor"></div>
                            <textarea x-ref="fallback" hidden wire:model="body_template" rows="18" class="w-full rounded border p-3"></textarea>
                        </div>

                        <p class="text-xs text-zinc-500">Untuk surat resmi, gunakan alignment dan tabel seperlunya. Ukuran font body PDF tetap dikunci 12 pt oleh generator agar konsisten.</p>
                        @error('body_template')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror

                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <div class="mb-3">
                                <h3 class="text-sm font-semibold">Tambah Variabel</h3>
                                <p class="text-xs text-zinc-500">Klik variabel saat kursor berada pada posisi yang diinginkan di editor.</p>
                            </div>
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Variabel Sistem</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($systemPlaceholderCatalog as $key => $label)
                                    <button type="button" x-on:click="insertPlaceholder(@js($key))" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">+ {{ $label }}</button>
                                @endforeach
                            </div>
                            <div class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-zinc-500">Variabel Isi</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($contentPlaceholderCatalog as $key => $label)
                                    <button type="button" x-on:click="insertPlaceholder(@js($key))" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">+ {{ $label }}</button>
                                @endforeach
                            </div>
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

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="requires_recipient"> Tujuan surat wajib diisi</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active"> Template aktif</label>
                    </div>
                    <div class="flex gap-2"><button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm text-white dark:bg-white dark:text-zinc-900">Simpan</button><button type="button" wire:click="cancel" class="rounded-lg border px-4 py-2 text-sm">Batal</button></div>
                </form>
            @else
                <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center text-sm text-zinc-500 dark:border-zinc-700">Pilih template untuk mengedit, atau klik <strong>+ Template Baru</strong> untuk membuat template sekolah sendiri.</div>
            @endif
        </div>
    </div>

</div>
