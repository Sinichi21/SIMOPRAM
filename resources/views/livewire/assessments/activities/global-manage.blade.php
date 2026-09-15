<div class="space-y-6">
    <header class="app-page-heading"><h1 class="text-2xl">Penilaian Kegiatan Umum</h1><p class="mt-2 text-sm">Kelola peserta, kriteria, juri, dan publikasi hasil lomba atau kegiatan umum.</p></header>
    @if (session('status'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('status') }}</p>@endif
    @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-6 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-3"><flux:heading size="lg">{{ $editingId ? 'Kelola form' : 'Buat form penilaian' }}</flux:heading><flux:button wire:click="newForm">Form baru</flux:button></div>
        <fieldset @disabled($selected && $selected->judges_count > 0 && $editingId === $selected->id) class="min-w-0 space-y-5 disabled:opacity-60">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="activityId" label="Kegiatan umum" required><option value="">Pilih kegiatan</option>@foreach ($activities as $activity)<option value="{{ $activity->id }}">{{ $activity->title }}</option>@endforeach</flux:select>
                <flux:input wire:model="title" label="Nama form / cabang lomba" required />
                <flux:select wire:model="mode" label="Jenis peserta"><option value="individual">Individu</option><option value="team">Regu / tim</option></flux:select>
            </div>
            <flux:textarea wire:model="participants" label="Nama peserta / tim (satu nama per baris)" description="Peserta tidak perlu memiliki akun atau terdaftar pada sekolah. Gunakan nama yang berbeda untuk setiap peserta." rows="5" required />
            <flux:button wire:click="useRegisteredParticipants">Ambil peserta terverifikasi dari pendaftaran</flux:button>
            <div class="space-y-4">
                <flux:heading>Kriteria penilaian</flux:heading>
                @foreach ($criteria as $index => $criterion)
                    <div wire:key="criterion-{{ $editingId ?? 'new' }}-{{ $index }}" class="grid gap-3 rounded-xl border border-zinc-200 p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-zinc-800">
                        <flux:input wire:model="criteria.{{ $index }}.name" label="Nama kriteria" required />
                        <flux:input wire:model="criteria.{{ $index }}.max_score" type="number" min="0.01" max="999999.99" step="0.01" label="Nilai maksimal" required />
                        <flux:input wire:model="criteria.{{ $index }}.weight" type="number" min="0" max="100" step="0.01" label="Bobot (%)" required />
                        <div class="flex items-end"><flux:button wire:click="removeCriterion({{ $index }})">Hapus kriteria</flux:button></div>
                        <div class="sm:col-span-2 lg:col-span-4"><flux:input wire:model="criteria.{{ $index }}.description" label="Petunjuk kriteria (opsional)" /></div>
                    </div>
                @endforeach
                <flux:button wire:click="addCriterion">Tambah kriteria</flux:button>
                <flux:text>Total bobot harus 100% saat form diaktifkan. Peserta dan kriteria dikunci setelah link juri dibuat.</flux:text>
            </div>
            <flux:button type="submit" variant="primary">Simpan Form</flux:button>
        </fieldset>
        @if ($selected && $selected->judges_count > 0 && $editingId === $selected->id)<flux:text>Form sudah dikunci karena link juri telah diterbitkan.</flux:text>@endif
    </form>
    <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:bg-zinc-900">
        <flux:heading size="lg">Daftar form penilaian</flux:heading>
        <flux:text>Publish Hasil menampilkan tiga peserta teratas dan tautan seluruh hasil pada detail kegiatan publik. Kegiatan juga harus diterbitkan.</flux:text>
        @forelse ($assessments as $assessment)
            <div wire:key="assessment-{{ $assessment->id }}" class="flex flex-col justify-between gap-3 border-t border-zinc-200 pt-4 sm:flex-row sm:items-center dark:border-zinc-800">
                <div><h3 class="font-semibold">{{ $assessment->title }}</h3><p class="mt-1 text-sm text-zinc-500">{{ $assessment->activity?->title }} · {{ $assessment->isPublished() ? 'Aktif' : 'Draft' }} · {{ $assessment->results_published_at ? 'Hasil publik' : 'Hasil belum publik' }}</p></div>
                <div class="flex flex-wrap gap-2">
                    <flux:button wire:click="edit({{ $assessment->id }})" size="sm">Kelola / Juri</flux:button>
                    @if (! $assessment->isPublished())<flux:button wire:click="activate({{ $assessment->id }})" size="sm">Aktifkan Form</flux:button>@endif
                    <flux:button wire:click="publishResults({{ $assessment->id }}, {{ $assessment->results_published_at ? 'false' : 'true' }})" wire:confirm="Ubah publikasi nama peserta dan hasil pada halaman publik?" size="sm">{{ $assessment->results_published_at ? 'Sembunyikan Hasil' : 'Publish Hasil' }}</flux:button>
                </div>
            </div>
        @empty
            <p class="py-6 text-zinc-500">Belum ada form penilaian umum.</p>
        @endforelse
    </section>
    @if ($selected?->isPublished())
        <livewire:assessments.activities.judges :assessment-id="$selected->id" :key="'global-judges-'.$selected->id" />
    @endif
</div>
