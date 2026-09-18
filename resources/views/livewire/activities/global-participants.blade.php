<div class="space-y-6">
    <header class="app-page-heading"><h1 class="text-2xl">Daftar peserta</h1><p>{{ $activity->title }}</p></header>
    @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="flex flex-wrap gap-3"><flux:button :href="route('admin.public-activities')">Kegiatan umum</flux:button><flux:button variant="primary" :href="route('admin.activity-participants.create', $activity->id)">Tambah peserta</flux:button><flux:button :href="route('admin.activity-registration-settings', $activity->id)">Pengaturan formulir</flux:button></div>
    <flux:button :href="route('admin.activity-attendance', $activity->id)">Absensi dan cetak daftar hadir</flux:button>
    <form wire:submit="sendAnnouncement" class="space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:heading>Pengumuman untuk peserta kegiatan</flux:heading>
        <flux:text>Pesan dikirim ke peserta dan pendamping aktif kegiatan ini melalui email atau WhatsApp yang dipilih saat pendaftaran, termasuk pengguna tautan sementara.</flux:text>
        <flux:input wire:model="notificationTitle" label="Judul pengumuman" required maxlength="150" />
        <flux:textarea wire:model="notificationBody" label="Isi pengumuman" required maxlength="5000" />
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="sendAnnouncement">Kirim ke peserta</flux:button>
    </form>
    @if($deliveries->isNotEmpty())
        <details class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <summary>Riwayat 10 pesan peserta terbaru</summary>
            <flux:text>Terkirim berarti diterima penyedia. Pesan gagal tidak dikirim ulang otomatis.</flux:text>
            <ul class="space-y-2 pt-3">
                @foreach($deliveries as $delivery)
                    <li wire:key="delivery-{{ $delivery->id }}">{{ $delivery->registration->name }} — {{ $delivery->title }} ({{ $delivery->channel }}): {{ ['pending' => 'Menunggu pengiriman', 'processing' => 'Diproses', 'sent' => 'Terkirim', 'skipped' => 'Dilewati', 'failed' => 'Gagal; periksa penyedia sebelum mengirim ulang'][$delivery->status] ?? $delivery->status }}</li>
                @endforeach
            </ul>
        </details>
    @endif
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <flux:input wire:model.live.debounce.300ms="search" label="Pencarian" placeholder="Nama, NTA/NIP, sekolah" />
        <flux:select wire:model.live="category" label="Kategori"><option value="">Semua kategori</option>@foreach(\App\Services\ActivityEntryService::CATEGORIES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</flux:select>
        <flux:select wire:model.live="status" label="Status"><option value="">Semua</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></flux:select>
        <flux:select wire:model.live="validation" label="Validasi"><option value="">Semua</option><option value="pending">Menunggu validasi</option><option value="validated">Tervalidasi</option></flux:select>
        <flux:select wire:model.live="order" label="Urutan"><option value="newest">Terbaru</option><option value="oldest">Terlama</option><option value="name">Nama A–Z</option><option value="name_desc">Nama Z–A</option></flux:select>
    </div>
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700"><table class="w-full text-left text-sm"><thead class="bg-zinc-100 dark:bg-zinc-800"><tr><th class="p-4">Peserta</th><th class="p-4">Kategori</th><th class="p-4">Status</th><th class="p-4">Tindakan</th></tr></thead><tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @forelse($entries as $entry)<tr wire:key="entry-{{ $entry->id }}"><td class="p-4 font-semibold">{{ $entry->name }}<p class="text-xs font-normal">{{ $entry->members_count }} orang termasuk pendamping / cadangan</p></td><td class="p-4">{{ \App\Services\ActivityEntryService::CATEGORIES[$entry->category] ?? $entry->category }}</td><td class="p-4">{{ $entry->status === 'active' ? 'Aktif' : 'Nonaktif' }}<p>{{ $entry->validation_status === 'validated' ? 'Tervalidasi' : 'Menunggu validasi' }}</p></td><td class="p-4"><div class="flex flex-wrap gap-2"><flux:button size="sm" wire:click="detail({{ $entry->id }})">Detail</flux:button><flux:button size="sm" :href="route('admin.activity-participants.edit', [$activity->id, $entry->id])">Edit</flux:button>@if($entry->status === 'active' && $entry->validation_status !== 'validated')<flux:button size="sm" wire:click="validateEntry({{ $entry->id }})">Validasi</flux:button>@endif<flux:button size="sm" wire:click="setActive({{ $entry->id }}, {{ $entry->status === 'active' ? 'false' : 'true' }})" wire:confirm="Ubah status dan akses seluruh anggota?">{{ $entry->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}</flux:button></div></td></tr>@empty<tr><td colspan="4" class="p-8 text-center">Belum ada peserta yang sesuai.</td></tr>@endforelse
    </tbody></table></div>{{ $entries->links() }}
    @if($selected)<section class="space-y-5 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><flux:heading size="lg">Detail: {{ $selected->name }}</flux:heading>
        <p class="text-sm">Pernyataan dan ketentuan disetujui {{ $selected->terms_accepted_at?->format('d-m-Y H:i') }}.</p>
        @foreach($selected->members as $member)<div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800"><p class="font-semibold">{{ $member->name }} · {{ $member->role === 'coach' ? 'Pembina pendamping' : ($member->is_reserve ? 'Cadangan' : 'Peserta') }}</p><p class="text-sm">{{ $member->identifier ?: 'Tanpa NTA/NIP' }} · {{ $member->school_name }} · {{ $member->student_id || $member->coach_id ? 'Data SIMPRAM' : 'Peserta eksternal' }}</p><p class="text-xs">Pengiriman akses: {{ ['pending'=>'Menunggu pengiriman','processing'=>'Sedang dikirim','sent'=>'Terkirim','failed'=>'Gagal dikirim','revoked'=>'Dicabut'][$member->delivery_status] ?? $member->delivery_status }}</p></div>@endforeach
        @foreach($selected->form_snapshot ?? [] as $field)@if($field['type'] !== 'file')<div><h3 class="font-semibold">{{ $field['label'] }}</h3><p class="whitespace-pre-line break-words text-sm">{{ is_array($answer = $selected->answers[$field['id']] ?? null) ? implode(', ', $answer) : ($answer ?? '—') }}</p></div>@endif@endforeach
        @foreach($selected->attachments ?? [] as $index => $file)<flux:button :href="route('admin.activity-participants.attachment', [$activity->id, $selected->id, $index])" target="_blank" rel="noopener noreferrer">Lihat {{ $file['name'] }}</flux:button>@endforeach
        <details><summary>Ketentuan yang disetujui</summary><p class="whitespace-pre-line text-sm">{{ $selected->terms_snapshot }}</p></details>
        @if($selected->status === 'active')<flux:button wire:click="resend({{ $selected->id }})" wire:confirm="Kirim ulang akses? Link sebelumnya akan dicabut.">Kirim ulang akses</flux:button>@endif<flux:text>Tautan dikirim langsung ke peserta. Pengelola hanya melihat status pengiriman.</flux:text>
    </section>@endif
</div>
