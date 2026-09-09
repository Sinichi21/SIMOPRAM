<div class="space-y-6">
    <style>
        /*
         * Panduan tabel KHUSUS Rich Template Editor.
         * Tidak disimpan ke body_template dan tidak ikut tercetak ke PDF.
         */
        .letter-template-editor table {
            border-collapse: collapse !important;
            table-layout: fixed;
            margin: 10px 0 !important;
            max-width: 100%;
        }
    
        .letter-template-editor td,
        .letter-template-editor th {
            min-width: 48px;
            height: 30px;
            padding: 6px 8px !important;
            vertical-align: top;
            /*
             * outline dipakai sebagai guide agar tetap terlihat walaupun
             * template final memang borderless.
             */
            outline: 1px dashed #94a3b8 !important;
            outline-offset: -1px;
        }
    
        .letter-template-editor th {
            background: #f8fafc;
            font-weight: 700;
        }
    
        .letter-template-editor td:hover,
        .letter-template-editor th:hover {
            background: #f8fafc;
        }
    
        .letter-template-editor td:focus,
        .letter-template-editor th:focus {
            background: #f1f5f9;
            outline: 2px solid #64748b !important;
            outline-offset: -2px;
        }
    
        .letter-template-editor td:empty::before,
        .letter-template-editor th:empty::before {
            content: "\00a0";
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
                        wire:key="rich-template-editor-{{ $editingId ?: 'new' }}-{{ md5($body_template) }}"
                        x-data="letterRichEditor(@js($body_template))"
                        x-init="init()"
                        class="space-y-2"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <label class="block text-sm font-medium">Isi Template</label>
                                <p class="mt-1 text-xs text-zinc-500">Times New Roman 12 pt. Hanya kop resmi dan footer QR yang tetap dikelola sistem; bagian lainnya dapat disusun di template.</p>
                            </div>
                            <span class="text-xs text-zinc-500">Variabel: @{{ nama_variabel }}</span>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-zinc-300 dark:border-zinc-700">
                            <div class="flex flex-wrap gap-1 border-b border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-700 dark:bg-zinc-950/60">
                                <select x-on:change="formatBlock($event.target.value); $event.target.value = 'p'" class="rounded border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900">
                                    <option value="p">Paragraf</option>
                                    <option value="blockquote">Kutipan</option>
                                </select>

                                <button type="button" x-on:click="command('bold')" title="Tebal" class="rounded border px-2.5 py-1 text-sm font-bold">B</button>
                                <button type="button" x-on:click="command('italic')" title="Miring" class="rounded border px-2.5 py-1 text-sm italic">I</button>
                                <button type="button" x-on:click="command('underline')" title="Garis bawah" class="rounded border px-2.5 py-1 text-sm underline">U</button>
                                <button type="button" x-on:click="command('strikeThrough')" title="Coret" class="rounded border px-2.5 py-1 text-sm line-through">S</button>

                                <span class="mx-1 h-7 w-px bg-zinc-300 dark:bg-zinc-700"></span>

                                <button type="button" x-on:click="align('left')" title="Rata kiri" class="rounded border px-2 py-1 text-xs">Kiri</button>
                                <button type="button" x-on:click="align('center')" title="Rata tengah" class="rounded border px-2 py-1 text-xs">Tengah</button>
                                <button type="button" x-on:click="align('right')" title="Rata kanan" class="rounded border px-2 py-1 text-xs">Kanan</button>
                                <button type="button" x-on:click="align('justify')" title="Rata kiri kanan" class="rounded border px-2 py-1 text-xs">Justify</button>

                                <span class="mx-1 h-7 w-px bg-zinc-300 dark:bg-zinc-700"></span>

                                <button type="button" x-on:click="command('insertUnorderedList')" class="rounded border px-2 py-1 text-xs">• List</button>
                                <button type="button" x-on:click="command('insertOrderedList')" class="rounded border px-2 py-1 text-xs">1. List</button>
                                <button type="button" x-on:click="command('outdent')" title="Kurangi indent" class="rounded border px-2 py-1 text-xs">← Indent</button>
                                <button type="button" x-on:click="command('indent')" title="Tambah indent" class="rounded border px-2 py-1 text-xs">Indent →</button>

                                <span class="mx-1 h-7 w-px bg-zinc-300 dark:bg-zinc-700"></span>

                                <button type="button" x-on:click="insertTable(2, 2)" class="rounded border px-2 py-1 text-xs">Tabel 2×2</button>
                                <button type="button" x-on:click="insertTable(3, 3)" class="rounded border px-2 py-1 text-xs">Tabel 3×3</button>
                                <button type="button" x-on:click="addTableRow()" class="rounded border px-2 py-1 text-xs">+ Baris</button>
                                <button type="button" x-on:click="addTableColumn()" class="rounded border px-2 py-1 text-xs">+ Kolom</button>
                                <button type="button" x-on:click="deleteTable()" class="rounded border border-red-300 px-2 py-1 text-xs text-red-600">Hapus Tabel</button>

                                <select
                                    x-on:change="setTableWidth($event.target.value); $event.target.value = ''"
                                    class="rounded border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                    title="Ubah lebar tabel aktif"
                                >
                                    <option value="">Lebar Tabel</option>
                                    <option value="auto">Auto</option>
                                    <option value="25%">25%</option>
                                    <option value="50%">50%</option>
                                    <option value="75%">75%</option>
                                    <option value="100%">100%</option>
                                </select>

                                <select
                                    x-on:change="setColumnWidth($event.target.value); $event.target.value = ''"
                                    class="rounded border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                    title="Ubah lebar kolom tempat kursor berada"
                                >
                                    <option value="">Lebar Kolom</option>
                                    <option value="20%">20%</option>
                                    <option value="25%">25%</option>
                                    <option value="30%">30%</option>
                                    <option value="33%">33%</option>
                                    <option value="40%">40%</option>
                                    <option value="50%">50%</option>
                                    <option value="60%">60%</option>
                                    <option value="67%">67%</option>
                                    <option value="70%">70%</option>
                                    <option value="75%">75%</option>
                                    <option value="80%">80%</option>
                                </select>

                                <button type="button" x-on:click="adjustColumnWidth(-5)" class="rounded border px-2 py-1 text-xs" title="Perkecil kolom aktif 5%">Kolom −</button>
                                <button type="button" x-on:click="adjustColumnWidth(5)" class="rounded border px-2 py-1 text-xs" title="Perbesar kolom aktif 5%">Kolom +</button>

                                <select
                                    x-on:change="setTableBorder($event.target.value); $event.target.value = ''"
                                    class="rounded border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                    title="Atur border tabel pada PDF"
                                >
                                    <option value="">Border Tabel</option>
                                    <option value="solid">Dengan Garis</option>
                                    <option value="none">Tanpa Garis</option>
                                    <option value="dashed">Putus-putus</option>
                                </select>

                                <select
                                    x-on:change="setRowVerticalAlign($event.target.value); $event.target.value = ''"
                                    class="rounded border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                    title="Atur posisi vertikal semua cell pada baris aktif"
                                >
                                    <option value="">Align Baris</option>
                                    <option value="top">Atas</option>
                                    <option value="middle">Tengah</option>
                                    <option value="bottom">Bawah</option>
                                </select>

                                <span class="mx-1 h-7 w-px bg-zinc-300 dark:bg-zinc-700"></span>

                                <button type="button" x-on:click="command('insertHorizontalRule')" class="rounded border px-2 py-1 text-xs">Garis Horizontal</button>
                                <button type="button" x-on:click="command('removeFormat')" class="rounded border px-2 py-1 text-xs">Bersihkan Format</button>
                                <button type="button" x-on:click="command('undo')" class="rounded border px-2 py-1 text-xs">Undo</button>
                                <button type="button" x-on:click="command('redo')" class="rounded border px-2 py-1 text-xs">Redo</button>
                            </div>

                            <div
                                x-ref="editor"
                                contenteditable="true"
                                spellcheck="true"
                                x-on:input.debounce.250ms="sync()"
                                x-on:keyup="rememberSelection()"
                                x-on:mouseup="rememberSelection()"
                                x-on:focus="rememberSelection()"
                                class="letter-template-editor min-h-[420px] bg-white p-5 text-black outline-none dark:bg-white"
                                style="font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5;"
                            ></div>
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

    @script
    <script>
        Alpine.data('letterRichEditor', (initialHtml) => ({
            savedRange: null,

            init() {
                this.$refs.editor.innerHTML = initialHtml || '<p><br></p>';
                this.sync();
            },

            focusEditor() {
                this.$refs.editor.focus();
                if (this.savedRange) {
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(this.savedRange);
                }
            },

            rememberSelection() {
                const selection = window.getSelection();
                if (selection && selection.rangeCount > 0 && this.$refs.editor.contains(selection.anchorNode)) {
                    this.savedRange = selection.getRangeAt(0).cloneRange();
                }
            },

            sync() {
                this.rememberSelection();
                this.$wire.set('body_template', this.$refs.editor.innerHTML, false);
            },

            command(name, value = null) {
                this.focusEditor();
                document.execCommand(name, false, value);
                this.sync();
            },

            formatBlock(tag) {
                this.focusEditor();
                document.execCommand('formatBlock', false, tag);
                this.sync();
            },

            align(value) {
                const commands = {
                    left: 'justifyLeft',
                    center: 'justifyCenter',
                    right: 'justifyRight',
                    justify: 'justifyFull',
                };
                this.command(commands[value]);
            },

            insertPlaceholder(key) {
                this.focusEditor();
                document.execCommand('insertText', false, '\u007B\u007B ' + key + ' \u007D\u007D');
                this.sync();
            },

            insertTable(rows, columns) {
                this.focusEditor();
                let html = '<table><tbody>';
                for (let r = 0; r < rows; r++) {
                    html += '<tr>';
                    for (let c = 0; c < columns; c++) {
                        html += '<td style="width: ' + (100 / columns).toFixed(2) + '%; border: 0.6pt solid #000;">&nbsp;</td>';
                    }
                    html += '</tr>';
                }
                html += '</tbody></table><p><br></p>';
                document.execCommand('insertHTML', false, html);
                this.sync();
            },

            selectionNode() {
                let node = this.savedRange?.startContainer || window.getSelection()?.anchorNode || null;

                if (node && node.nodeType === Node.TEXT_NODE) {
                    node = node.parentElement;
                }

                return node;
            },

            currentCell() {
                return this.selectionNode()?.closest?.('td,th') || null;
            },

            currentTable() {
                return this.currentCell()?.closest('table')
                    || this.selectionNode()?.closest?.('table')
                    || null;
            },

            setTableWidth(width) {
                const table = this.currentTable();

                if (!table || !width) return;

                table.style.width = width;
                table.style.maxWidth = '100%';

                this.sync();
            },

            setColumnWidth(width) {
                const cell = this.currentCell();

                if (!cell || !width) return;

                const table = cell.closest('table');
                const index = Array.from(cell.parentElement.children).indexOf(cell);

                table.querySelectorAll('tr').forEach((row) => {
                    const target = row.children[index];

                    if (target) {
                        target.style.width = width;
                    }
                });

                this.sync();
            },

            adjustColumnWidth(delta) {
                const cell = this.currentCell();

                if (!cell) return;

                const current = parseFloat(cell.style.width || '0');
                const base = Number.isFinite(current) && current > 0
                    ? current
                    : (100 / Math.max(1, cell.parentElement.children.length));

                const next = Math.max(5, Math.min(95, base + delta));

                this.setColumnWidth(next.toFixed(0) + '%');
            },

            setTableBorder(mode) {
                const table = this.currentTable();

                if (!table || !mode) return;

                const border = mode === 'none'
                    ? '0'
                    : mode === 'dashed'
                        ? '0.6pt dashed #000'
                        : '0.6pt solid #000';

                table.querySelectorAll('td,th').forEach((cell) => {
                    cell.style.border = border;
                });

                this.sync();
            },

            setRowVerticalAlign(value) {
                const cell = this.currentCell();

                if (!cell || !['top', 'middle', 'bottom'].includes(value)) return;

                const row = cell.closest('tr');

                row?.querySelectorAll('td,th').forEach((rowCell) => {
                    rowCell.style.verticalAlign = value;
                });

                this.sync();
            },

            addTableRow() {
                const cell = this.currentCell();
                if (!cell) return;
                const row = cell.closest('tr');
                const clone = row.cloneNode(true);
                clone.querySelectorAll('td,th').forEach((item) => item.innerHTML = '&nbsp;');
                row.parentNode.insertBefore(clone, row.nextSibling);
                this.sync();
            },

            addTableColumn() {
                const cell = this.currentCell();
                if (!cell) return;
                const index = Array.from(cell.parentElement.children).indexOf(cell);
                const table = cell.closest('table');
                table.querySelectorAll('tr').forEach((row) => {
                    const source = row.children[Math.min(index, row.children.length - 1)];
                    const newCell = source.cloneNode(false);
                    newCell.innerHTML = '&nbsp;';
                    if (source.nextSibling) row.insertBefore(newCell, source.nextSibling);
                    else row.appendChild(newCell);
                });
                this.sync();
            },

            deleteTable() {
                const cell = this.currentCell();
                const table = cell?.closest('table');
                if (!table) return;
                table.remove();
                this.sync();
            },
        }));
    </script>
    @endscript
</div>
