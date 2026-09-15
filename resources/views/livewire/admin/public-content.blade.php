<div class="space-y-6">
    <header class="app-page-heading">
        <h1 class="text-2xl">{{ $kind === 'activities' ? 'Kegiatan Umum' : 'Pengumuman Umum' }}</h1>
        <p class="mt-2 text-sm">Informasi umum untuk landing page. Pengajuan kegiatan dari admin sekolah memerlukan persetujuan super admin.</p>
    </header>
    @if (session('status'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('status') }}</p>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if($editingId || auth()->user()->isSuperAdmin() || $requestSchools->isNotEmpty())
    <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-6 dark:bg-zinc-900">
        <flux:heading size="lg">{{ $editingId ? 'Edit informasi' : 'Buat informasi' }}</flux:heading>
        <flux:input wire:model="title" label="Judul" required maxlength="200" />
        <flux:textarea wire:model="body" label="Isi lengkap / deskripsi" rows="6" required />
        @if ($kind === 'activities')
            <flux:select wire:model="parentActivityId" label="Agenda induk (opsional)"><option value="">Agenda utama / berdiri sendiri</option>@if($parentActivityId && ! $parentActivities->contains('id', $parentActivityId))<option value="{{ $parentActivityId }}">Agenda induk saat ini</option>@endif @foreach($parentActivities as $parent)<option value="{{ $parent->id }}">{{ $parent->title }}</option>@endforeach</flux:select>
            <flux:text>Pilih agenda induk untuk membuat subagenda, misalnya lomba pionering di dalam Pandu Trisma Cup. Peserta, registrasi, penilaian, dan delegasi dikelola per subagenda.</flux:text>
            @if(! auth()->user()->isSuperAdmin() && ! $editingId)<flux:select wire:model="requestSchoolId" label="Sekolah pengaju" required><option value="">Pilih sekolah</option>@foreach($requestSchools as $requestSchool)<option value="{{ $requestSchool->id }}">{{ $requestSchool->name }}</option>@endforeach</flux:select>@endif
            <flux:checkbox wire:model="registrationOpen" label="Aktifkan tombol registrasi peserta" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="activityType" label="Jenis kegiatan">
                    @foreach (['competition' => 'Lomba', 'camp' => 'Perkemahan', 'training' => 'Pelatihan', 'ceremony' => 'Upacara', 'service' => 'Bakti sosial', 'other' => 'Lainnya'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="location" label="Lokasi" maxlength="255" />
                <flux:input wire:model="startsAt" type="datetime-local" label="Mulai kegiatan" required />
                <flux:input wire:model="endsAt" type="datetime-local" label="Selesai kegiatan" required />
            </div>
        @else
            <flux:input wire:model="expiresAt" type="datetime-local" label="Berlaku sampai (opsional)" />
        @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model="status" label="Status">
                <option value="draft">Draft</option><option value="published">Terbit</option>
                @if ($kind === 'activities')<option value="ongoing">Berlangsung</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option>@endif
            </flux:select>
            <flux:input wire:model="publishedAt" type="datetime-local" label="Tanggal publikasi (kosong = sekarang)" />
        </div>
        <flux:text>Informasi berstatus draft tidak ditampilkan. Tanggal publikasi mendatang akan tampil setelah waktunya tiba. Waktu menggunakan {{ config('app.timezone') }}.</flux:text>
        @include('partials.content-media-fields', ['withBanner' => $kind === 'activities'])
        <div class="flex gap-3"><flux:button type="submit" variant="primary">Simpan</flux:button>@if ($editingId)<flux:button wire:click="cancelEdit">Batal edit</flux:button>@endif</div>
    </form>
    @endif
    <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:bg-zinc-900">
        <flux:input wire:model.live.debounce.300ms="search" label="Cari informasi" placeholder="Cari judul..." />
        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @forelse ($records as $record)
                <div wire:key="content-{{ $record->id }}" class="flex flex-col justify-between gap-3 py-4 sm:flex-row sm:items-center">
                    <div class="min-w-0"><h2 class="font-semibold break-words">{{ $record->title }}</h2><p class="mt-1 text-sm text-zinc-500">{{ ['draft' => 'Draft', 'published' => 'Terbit', 'ongoing' => 'Berlangsung', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$record->status] ?? $record->status }} · {{ $record->published_at?->format('d-m-Y H:i') ?? 'Belum diterbitkan' }}</p></div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button wire:click="edit({{ $record->id }})" size="sm">Edit</flux:button>
                        @if ($kind === 'activities')
                            @if($record->approval_status === 'approved')<flux:button :href="route('admin.activity-attendance', $record->id)" size="sm">Absensi</flux:button>@endif
                            @if($record->parent_activity_id)<span class="text-sm font-semibold">Subagenda{{ ($parentName = $parentActivities->firstWhere('id', $record->parent_activity_id)?->title) ? ': '.$parentName : '' }}</span>@endif
                            <span class="text-sm">{{ ['pending'=>'Menunggu persetujuan','approved'=>'Disetujui','rejected'=>'Ditolak'][$record->approval_status] ?? $record->approval_status }}</span>
                            @if($record->rejection_reason)<p class="text-sm">{{ $record->rejection_reason }}</p>@endif
                            @if($record->approval_status === 'pending' && auth()->user()->isSuperAdmin())<flux:input wire:model="rejectionReason" label="Alasan (wajib jika menolak)" /><flux:button size="sm" wire:click="reviewActivity({{ $record->id }}, true)">Setujui</flux:button><flux:button size="sm" wire:click="reviewActivity({{ $record->id }}, false)">Tolak</flux:button>@endif
                            @if($record->approval_status === 'approved')<flux:button :href="route('admin.public-assessments', ['activity' => $record->id])" size="sm">Penilaian</flux:button><flux:button :href="route('admin.activity-participants', $record->id)" size="sm">Daftar peserta</flux:button><flux:button :href="route('admin.activity-registration-settings', $record->id)" size="sm">Form registrasi</flux:button><flux:button :href="route('admin.activity-delegates', $record->id)" size="sm">Delegasi</flux:button>@endif
                        @endif
                        @if (in_array($record->status, ['published', 'ongoing', 'completed']) && (! $record->published_at || $record->published_at->lte(now())) && ($kind === 'activities' || ! $record->expires_at || $record->expires_at->gt(now())))
                            <flux:button :href="route($kind === 'activities' ? 'public.activities.show' : 'public.announcements.show', $record->id)" size="sm">Lihat publik</flux:button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-zinc-500">Belum ada informasi umum.</p>
            @endforelse
        </div>
        {{ $records->links() }}
    </section>
</div>
