<div class="mx-auto max-w-5xl space-y-6">
    <header class="app-page-heading">
        <h1 class="text-3xl font-black">{{ $school ? 'Edit halaman tenant' : 'Edit landing page' }}</h1>
        <p class="mt-2">{{ $school?->name ?? 'Halaman utama SIMPRAM' }}</p>
    </header>
    @if (session('success'))
        <div role="status" class="rounded-2xl bg-emerald-100 p-4 text-emerald-900">{{ session('success') }}</div>
    @endif
    <form wire:submit="save" class="space-y-6">
        <section class="grid gap-5 rounded-3xl border border-zinc-200 bg-white p-6 sm:grid-cols-2 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-xl font-bold sm:col-span-2">Konten halaman</h2>
            @foreach ($fields as $key => $field)
                <div wire:key="content-{{ $key }}" @class(['sm:col-span-2' => ($field['type'] ?? '') === 'textarea'])>
                    @if (($field['type'] ?? '') === 'textarea')
                        <flux:textarea wire:model="content.{{ $key }}" :label="$field['label']" rows="4" />
                    @elseif (($field['type'] ?? '') === 'checkbox')
                        <flux:checkbox wire:model="content.{{ $key }}" :label="$field['label']" />
                    @else
                        <flux:input wire:model="content.{{ $key }}" :label="$field['label']" :type="$field['type'] ?? 'text'" />
                    @endif
                    <flux:error name="content.{{ $key }}" />
                </div>
            @endforeach
            <flux:error name="content" />
        </section>
        <section class="grid gap-6 rounded-3xl border border-zinc-200 bg-white p-6 sm:grid-cols-2 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="space-y-4">
                <flux:input type="file" wire:model="heroUpload" label="Foto utama" accept="image/jpeg,image/png,image/webp" />
                <p class="text-xs text-zinc-500">JPG, PNG, atau WebP. Maksimal 4 MB.</p>
                <flux:error name="heroUpload" />
                @if ($heroImage)
                    <img src="{{ Storage::disk('public')->url($heroImage) }}" alt="Foto utama saat ini" class="aspect-video w-full rounded-2xl object-cover">
                    <flux:checkbox wire:model="removeHero" label="Hapus foto utama" />
                @endif
            </div>
            @if ($school)
                <div class="space-y-4">
                    <flux:input type="file" wire:model="logoUpload" label="Logo sekolah" accept="image/jpeg,image/png,image/webp" />
                    <p class="text-xs text-zinc-500">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
                    <flux:error name="logoUpload" />
                    @if ($logoImage)
                        <img src="{{ Storage::disk('public')->url($logoImage) }}" alt="Logo saat ini" class="size-24 rounded-xl object-contain">
                        <flux:checkbox wire:model="removeLogo" label="Hapus logo" />
                    @endif
                </div>
            @endif
        </section>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:button :href="$school ? route('schools.landing', $school) : route('home')" target="_blank" rel="noopener">Lihat halaman publik</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save,heroUpload,logoUpload">Simpan konten</flux:button>
            <span wire:loading wire:target="heroUpload,logoUpload" class="text-sm text-zinc-500">Mengunggah gambar…</span>
        </div>
    </form>
    @if ($school)
        <p class="text-sm text-zinc-500">Pengumuman, kegiatan, dokumentasi, dan akun pembina dikelola melalui menu masing-masing di dashboard.</p>
    @endif
</div>
