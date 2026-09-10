@php
    $incoming = $direction === 'incoming';
    $title = $incoming ? 'Surat Masuk' : 'Surat Keluar';
    $statuses = $incoming
        ? ['recorded' => 'Tercatat', 'disposed' => 'Didisposisikan', 'archived' => 'Diarsipkan']
        : ['draft' => 'Draft', 'published' => 'Terbit', 'cancelled' => 'Dibatalkan'];
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $title }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ $incoming ? 'Agenda, klasifikasi keamanan, dan arsip surat yang diterima.' : 'Gunakan template untuk mengisi variabel surat, melihat preview, menyimpan draft, lalu menerbitkan dengan nomor otomatis.' }}</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @canany(['letters.create', 'letters.update'])
        <form wire:submit="save" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">{{ $editingId ? 'Edit' : 'Tambah' }} {{ $title }}</h2>
                @if ($editingId)<button type="button" wire:click="cancelEdit" class="text-sm text-zinc-500">Batal</button>@endif
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @unless ($incoming)
                    <div class="md:col-span-2 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/30">
                        <label class="mb-1 block text-sm font-semibold">Template Surat</label>
                        <select wire:model.live="template_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="">Tanpa template / isi bebas</option>
                            @foreach ($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach
                        </select>
                        <p class="mt-1 text-xs text-zinc-500">Saat template dipilih, Jenis Surat, Bidang default, serta form variabel akan diisi otomatis.</p>
                    </div>

                    <div><label class="mb-1 block text-sm font-medium">Nomor Surat</label><input wire:model="letter_number" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" placeholder="Kosongkan agar otomatis saat terbit"><p class="mt-1 text-xs text-zinc-500">Nomor otomatis baru diambil saat status Terbit.</p></div>
                    <div><label class="mb-1 block text-sm font-medium">Jenis Surat {{ $status === 'published' ? '*' : '' }}</label><select wire:model="letter_type_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"><option value="">Pilih jenis</option>@foreach ($types as $type)<option value="{{ $type->id }}">{{ $type->code }} - {{ $type->name }}</option>@endforeach</select>@error('letter_type_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-sm font-medium">Bidang {{ $status === 'published' ? '*' : '' }}</label><select wire:model="letter_field_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"><option value="">Pilih bidang</option>@foreach ($fields as $field)<option value="{{ $field->id }}">{{ $field->code }} - {{ $field->name }}</option>@endforeach</select>@error('letter_field_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror</div>
                @endunless

                <div><label class="mb-1 block text-sm font-medium">Tanggal Surat *</label><input type="date" wire:model.live="letter_date" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">@error('letter_date')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror</div>
                @if ($incoming)<div><label class="mb-1 block text-sm font-medium">Tanggal Diterima *</label><input type="date" wire:model="received_date" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>@endif
                @if ($incoming)<div><label class="mb-1 block text-sm font-medium">Pengirim *</label><input wire:model="sender" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>@else<div><label class="mb-1 block text-sm font-medium">Tujuan {{ $selectedTemplate?->requires_recipient ? "*" : "(opsional)" }}</label><input wire:model.live.debounce.300ms="recipient" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">@error('recipient')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror</div>@endif
                <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium">Perihal *</label><input wire:model.live.debounce.300ms="subject" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>

                @unless ($incoming)
                    @if ($selectedTemplate && $templatePlaceholders)
                        <div class="md:col-span-2 rounded-xl border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <div><h3 class="font-semibold">Isi Data Template</h3><p class="text-xs text-zinc-500">Lengkapi variabel yang digunakan oleh <strong>{{ $selectedTemplate->name }}</strong>.</p></div>
                                <button type="button" wire:click="togglePreview" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-medium dark:border-zinc-700 dark:bg-zinc-900">{{ $showPreview ? 'Sembunyikan Preview' : 'Tampilkan Preview' }}</button>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ($templatePlaceholders as $placeholder)
                                    @php($label = $placeholderCatalog[$placeholder] ?? str($placeholder)->replace('_', ' ')->title())
                                    <div class="{{ in_array($placeholder, ['considerations','legal_basis','assignees','assignment_purpose','additional_information','announcement_body','note_body','introduction','activity_execution','evaluation','closing','statement'], true) ? 'md:col-span-2' : '' }}">
                                        <label class="mb-1 block text-sm font-medium">{{ $label }}</label>
                                        @if (in_array($placeholder, ['considerations','legal_basis','assignees','assignment_purpose','additional_information','announcement_body','note_body','introduction','activity_execution','evaluation','closing','statement','request_detail','received_items','delivered_items'], true))
                                            <textarea wire:model.live.debounce.300ms="templateData.{{ $placeholder }}" rows="3" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                                        @else
                                            <input wire:model.live.debounce.300ms="templateData.{{ $placeholder }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                                        @endif
                                        @error('templateData.'.$placeholder)<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($selectedTemplate && $showPreview)
                        @include('livewire.letters.partials.a4-preview')
                    @endif
                @endunless

                <div><label class="mb-1 block text-sm font-medium">Klasifikasi Keamanan</label><select wire:model="security_classification" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"><option>Biasa</option><option>Terbatas</option><option>Rahasia</option></select></div>
                <div><label class="mb-1 block text-sm font-medium">Status *</label><select wire:model.live="status" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">@foreach ($statuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-medium">Kode Arsip</label><input wire:model="archive_code" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" placeholder="01 / 02 / 03 ..."></div>
                <div><label class="mb-1 block text-sm font-medium">Kategori Arsip</label><input wire:model="archive_category" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" placeholder="Kegiatan Kepramukaan"></div>
                <div><label class="mb-1 block text-sm font-medium">Retensi Arsip</label><select wire:model="retention_years" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"><option value="">Belum ditentukan</option><option value="2">2 tahun</option><option value="5">5 tahun</option><option value="10">10 tahun</option><option value="20">Lebih dari 10 tahun / statis</option></select></div>
                <div><label class="mb-1 block text-sm font-medium">Klasifikasi / Jenis bebas</label><input wire:model="classification" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></div>

                @if ($incoming || ! $selectedTemplate)
                    <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium">Isi / Catatan</label><textarea wire:model="body" rows="8" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></textarea></div>
                @else
                    <div class="md:col-span-2 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-950/40 dark:text-zinc-400">Isi surat dihasilkan dari Template + Data Template. Hasil render disimpan sebagai snapshot pada field <code>body</code> ketika surat disimpan.</div>
                @endif

                @unless ($incoming)
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium">Sumber Penandatangan / TTD</label>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" value="master" wire:model.live="signatory_source">
                                <span>Ambil dari Master Data</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" value="manual" wire:model.live="signatory_source">
                                <span>Input Manual</span>
                            </label>
                        </div>
                    </div>

                    @if ($signatory_source === 'master')
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Pilih User Penandatangan</label>
                            <select wire:model.live="signatory_user_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                                <option value="">-- Pilih User dari Master Data --</option>
                                @foreach ($signatoryUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-zinc-500">
                                Nama, jabatan, dan NIP/NTA terisi otomatis dari Pengaturan → Pengaturan Dokumen.
                            </p>
                            @error('signatory_user_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="md:col-span-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-300">
                            Mode manual dipakai untuk penandatangan yang belum terdaftar sebagai user/master data.
                            Data manual hanya disimpan pada surat ini dan tidak membuat user baru.
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium">Nama Penandatangan</label>
                        <input
                            wire:model.live.debounce.300ms="signatory_name"
                            @readonly($signatory_source === 'master')
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 {{ $signatory_source === 'master' ? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-900' : 'bg-white' }}"
                        >
                        @error('signatory_name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Jabatan</label>
                        <input
                            wire:model.live.debounce.300ms="signatory_position"
                            @readonly($signatory_source === 'master')
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 {{ $signatory_source === 'master' ? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-900' : 'bg-white' }}"
                        >
                        @error('signatory_position') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">NTA / NIP / Identitas</label>
                        <input
                            wire:model.live.debounce.300ms="signatory_identity"
                            @readonly($signatory_source === 'master')
                            placeholder="Contoh: NTA. 22.09.03.xxx atau NIP. 1989..."
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 {{ $signatory_source === 'master' ? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-900' : 'bg-white' }}"
                        >
                    </div>
                @endunless

                @can('letters.attachments')
                    <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium">Lampiran</label><input type="file" wire:model="attachments" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"></div>
                @endcan
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">{{ $editingId ? 'Simpan Perubahan' : 'Simpan Surat' }}</button>
                @unless ($incoming)<span class="text-xs text-zinc-500">Draft boleh disimpan walau variabel template belum lengkap. Semua variabel wajib lengkap saat status Terbit.</span>@endunless
            </div>
        </form>
    @endcanany

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-3 border-b border-zinc-200 p-4 md:grid-cols-2 dark:border-zinc-800"><input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nomor, perihal, pengirim/tujuan..." class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"><select wire:model.live="statusFilter" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"><option value="">Semua status</option>@foreach ($statuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800"><thead class="bg-zinc-50 dark:bg-zinc-950/50"><tr><th class="px-4 py-3 text-left">Nomor</th><th class="px-4 py-3 text-left">Tanggal</th><th class="px-4 py-3 text-left">{{ $incoming ? 'Pengirim' : 'Tujuan' }}</th><th class="px-4 py-3 text-left">Perihal</th><th class="px-4 py-3 text-left">Jenis/Bidang</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @forelse ($letters as $letter)
                <tr><td class="px-4 py-3 align-top"><div class="font-medium">{{ $letter->letter_number ?: '-' }}</div>@if ($letter->agenda_number)<div class="text-xs text-zinc-500">Agenda: {{ $letter->agenda_number }}</div>@endif<div class="text-xs text-zinc-500">{{ $letter->security_classification ?: 'Biasa' }}</div></td><td class="px-4 py-3 align-top">{{ $letter->letter_date->format('d/m/Y') }}</td><td class="px-4 py-3 align-top">{{ $incoming ? $letter->sender : ($letter->recipient ?: '-') }}</td><td class="px-4 py-3 align-top"><div class="max-w-xs font-medium">{{ $letter->subject }}</div>@if ($letter->archive_code)<div class="text-xs text-zinc-500">Arsip: {{ $letter->archive_code }}</div>@endif</td><td class="px-4 py-3 align-top">{{ $letter->letterType?->code ?: '-' }} / {{ $letter->letterField?->code ?: '-' }}</td><td class="px-4 py-3 align-top">{{ $statuses[$letter->status] ?? $letter->status }}</td><td class="whitespace-nowrap px-4 py-3 text-right">
    @if (! $incoming && $letter->publication)
        <a
            href="{{ route('reports.published-documents.show', ['code' => $letter->publication->code]) }}"
            wire:navigate
            class="mr-2 text-blue-600"
        >Detail</a>
        @if (! $letter->publication->isRevoked() && $letter->publication->hasArchivedPdf())
            @can('reports.export')
                <a
                    href="{{ route('reports.published-documents.download', ['code' => $letter->publication->code]) }}"
                    class="mr-2 text-emerald-600"
                >PDF</a>
            @endcan
        @endif
        <a
            href="{{ route('reports.verify', ['code' => $letter->publication->code]) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="text-zinc-600"
        >Verifikasi</a>
    @else
        @can('letters.update')
            <button type="button" wire:click="edit({{ $letter->id }})" class="mr-2 text-blue-600">Edit</button>
        @endcan
        @can('letters.delete')
            <button type="button" wire:click="delete({{ $letter->id }})" wire:confirm="Hapus surat ini?" class="text-red-600">Hapus</button>
        @endcan
    @endif
</td></tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-zinc-500">Belum ada data.</td></tr>
            @endforelse
        </tbody></table></div><div class="p-4">{{ $letters->links() }}</div>
    </div>
</div>
