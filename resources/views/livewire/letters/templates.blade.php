<div class="space-y-6">
    <style>
        .ck-editor__editable_inline {
            min-height: 420px;
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
        }

        .ck-editor__editable_inline table {
            border-collapse: collapse;
            table-layout: fixed;
            max-width: 100%;
        }

        .letter-real-preview {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.35;
        }

        .letter-real-preview p,
        .letter-real-preview div {
            margin: 0 0 2.6mm;
        }

        .letter-real-preview table {
            border-collapse: collapse;
            margin: 1.5mm 0 3mm;
            width: 100%;
        }

        .letter-real-preview td,
        .letter-real-preview th {
            padding: 1.3mm 1.8mm;
            vertical-align: top;
        }
    </style>
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
                        wire:key="rich-template-editor-{{ $editorVersion }}"
                        x-data
                        x-init="window.loadSimopramLetterEditor().then(({ mount }) => mount($refs.editor, @js($body_template), value => $wire.set('body_template', value)))"
                        x-on:livewire:navigating.window="window.loadSimopramLetterEditor().then(({ destroy }) => destroy($refs.editor))"
                        class="space-y-2"
                    >
                        <div>
                            <label class="block text-sm font-medium">Isi Template</label>
                            <p class="mt-1 text-xs text-zinc-500">Gunakan toolbar CKEditor 5 untuk format teks, heading, alignment, daftar, kutipan, dan tabel.</p>
                        </div>

                        <div
                            wire:ignore
                            data-ckeditor-editor
                            class="overflow-hidden rounded-xl border border-zinc-300 bg-white dark:border-zinc-700"
                        >
                            <div x-ref="editor" data-ckeditor-host></div>
                        </div>

                        <p class="text-xs text-zinc-500">Isi template akan disanitasi saat disimpan. Ukuran font body PDF tetap dikunci 12 pt oleh generator.</p>
                        @error('body_template')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror

                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <div class="mb-3">
                                <h3 class="text-sm font-semibold">Tambah Variabel</h3>
                                <p class="text-xs text-zinc-500">Klik variabel saat kursor berada pada posisi yang diinginkan di editor.</p>
                            </div>
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Variabel Sistem</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($systemPlaceholderCatalog as $key => $label)
                                    <button type="button" x-on:click="window.loadSimopramLetterEditor().then(({ insertPlaceholder }) => insertPlaceholder($refs.editor, @js($key)))" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">+ {{ $label }}</button>
                                @endforeach
                            </div>
                            <div class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-zinc-500">Variabel Isi</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($contentPlaceholderCatalog as $key => $label)
                                    <button type="button" x-on:click="window.loadSimopramLetterEditor().then(({ insertPlaceholder }) => insertPlaceholder($refs.editor, @js($key)))" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">+ {{ $label }}</button>
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

                    <section class="space-y-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h3 class="font-semibold">Preview Template A4</h3>
                                <p class="mt-1 text-xs text-zinc-500">Preview menggunakan contoh data. Perubahan isi editor akan diperbarui di sini.</p>
                            </div>
                            <label class="grid gap-1 text-xs font-medium">
                                <span>Kop Administrasi</span>
                                <select wire:model.live="previewAdministrationType" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                    <option value="mabigus">Mabigus</option>
                                    <option value="male">Gudep Putra</option>
                                    <option value="female">Gudep Putri</option>
                                </select>
                            </label>
                        </div>

                        <div class="overflow-x-auto pb-2">
                            <div class="mx-auto w-max">
                                <article class="bg-white text-black shadow-lg ring-1 ring-zinc-300" style="width: 210mm; min-height: 297mm; padding: 12mm 16mm 20mm; zoom: .72; font-family: 'Times New Roman', Times, serif;">
                                    <header style="position: relative; min-height: 22mm; border-bottom: 3px double #000; margin-bottom: 4mm; padding: 0 2mm 2.5mm; text-align: center;">
                                        <img src="{{ asset('images/reports/logo-pramuka.png') }}" alt="Logo Gerakan Pramuka" style="position: absolute; top: 0; left: 1mm; height: 21mm; max-width: 23mm; object-fit: contain;">
                                        <img src="{{ asset('images/reports/logo-wosm.png') }}" alt="Logo WOSM" style="position: absolute; top: 0; right: 1mm; height: 21mm; max-width: 23mm; object-fit: contain;">
                                        <div style="margin: 0 23mm; font-size: 14pt; font-weight: bold; line-height: 1.05;">
                                            {{ $previewAdministrationProfile?->letterhead_title ?: 'GERAKAN PRAMUKA' }}<br>
                                            @if (in_array($previewAdministrationType, ['male', 'mabigus'], true) && filled($previewDocumentSetting?->gudep_male_number))
                                                GUGUSDEPAN {{ strtoupper($previewSchool?->city ?: '') }} {{ $previewDocumentSetting->gudep_male_number }}<br>
                                            @endif
                                            @if (in_array($previewAdministrationType, ['female', 'mabigus'], true) && filled($previewDocumentSetting?->gudep_female_number))
                                                GUGUSDEPAN {{ strtoupper($previewSchool?->city ?: '') }} {{ $previewDocumentSetting->gudep_female_number }}<br>
                                            @endif
                                            PANGKALAN {{ strtoupper($previewAdministrationProfile?->letterhead_subtitle ?: ($previewSchool?->name ?: 'SEKOLAH')) }}
                                        </div>
                                        <div style="margin: .8mm 23mm 0; font-size: 10pt; line-height: 1.1;">{{ $previewAddress }}</div>
                                    </header>

                                    <table style="width: 100%; border-collapse: collapse; margin: 0 0 3mm; font-size: 12pt;">
                                        <tbody>
                                            <tr><td style="width: 22mm; padding: .25mm 0;">Nomor</td><td style="width: 4mm; text-align: center;">:</td><td>{{ $previewContext['letter_number'] }}</td></tr>
                                            <tr><td style="padding: .25mm 0;">Lampiran</td><td style="text-align: center;">:</td><td>1 berkas</td></tr>
                                            <tr><td style="padding: .25mm 0;">Perihal</td><td style="text-align: center;">:</td><td><strong>{{ $title ?: 'Perihal Surat' }}</strong></td></tr>
                                        </tbody>
                                    </table>
                                    <div class="letter-real-preview" style="font-size: 12pt; line-height: 1.35;">{!! $previewBody !!}</div>
                                    <div style="width: 48%; margin: 12mm 0 0 auto; text-align: center; font-size: 12pt;">
                                        Jabatan Penandatangan,
                                        <div style="height: 14mm;"></div>
                                        <strong><u>Nama Penandatangan</u></strong><br>
                                        NTA / Identitas
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>

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
