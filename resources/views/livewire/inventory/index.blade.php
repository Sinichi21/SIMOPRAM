<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Inventaris</flux:heading>
            <flux:subheading>Kelola data barang, kategori, dan jenis pelacakan inventaris sekolah.</flux:subheading>
        </div>
        @can('inventory.manage')
            <div class="flex flex-wrap gap-2">
                <flux:button variant="outline" icon="tag" wire:click="$set('showCategoryForm', true)">Kategori</flux:button>
                <flux:button variant="primary" icon="plus" wire:click="$set('showItemForm', true)">Tambah Barang</flux:button>
            </div>
        @endcan
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    @if ($showCategoryForm)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Kategori Baru</flux:heading>
            <form wire:submit="createCategory" class="space-y-4">
                <flux:input label="Nama kategori" wire:model="categoryName" required maxlength="100" />
                <flux:textarea label="Deskripsi (opsional)" wire:model="categoryDescription" />
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">Simpan Kategori</flux:button>
                    <flux:button type="button" variant="ghost" wire:click="$set('showCategoryForm', false)">Batal</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    @if ($showItemForm)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Barang Baru</flux:heading>
            <form wire:submit="createItem" class="grid gap-4 md:grid-cols-2">
                <flux:select label="Kategori" wire:model="categoryId" placeholder="Pilih kategori" required>
                    @foreach ($categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input label="Kode barang" wire:model="itemCode" required maxlength="60" placeholder="CONTOH: TENDA-001" />
                <flux:input label="Nama barang" wire:model="itemName" required maxlength="180" />
                <flux:select label="Metode pelacakan" wire:model="trackingType" required>
                    <flux:select.option value="quantity">Stok berdasarkan kuantitas</flux:select.option>
                    <flux:select.option value="individual">Aset per unit (barcode/QR)</flux:select.option>
                </flux:select>
                <flux:input label="Satuan" wire:model="unit" required maxlength="30" placeholder="unit, buah, meter..." />
                <flux:input label="Batas minimum stok" type="number" min="0" step="0.001" wire:model="minimumStock" required />
                <div class="md:col-span-2">
                    <flux:textarea label="Deskripsi (opsional)" wire:model="itemDescription" />
                </div>
                <div class="md:col-span-2 flex gap-2">
                    <flux:button type="submit" variant="primary">Simpan Barang</flux:button>
                    <flux:button type="button" variant="ghost" wire:click="$set('showItemForm', false)">Batal</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    <flux:card class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="lg">Daftar Barang</flux:heading>
            <div class="w-full sm:max-w-sm">
                <flux:input icon="magnifying-glass" placeholder="Cari nama atau kode barang..." wire:model.live.debounce.300ms="search" />
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="px-3 py-3 font-medium">Kode</th>
                        <th class="px-3 py-3 font-medium">Nama Barang</th>
                        <th class="px-3 py-3 font-medium">Kategori</th>
                        <th class="px-3 py-3 font-medium">Pelacakan</th>
                        <th class="px-3 py-3 font-medium text-right">Aset Unit</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr wire:key="inventory-item-{{ $item->id }}" class="border-b last:border-0">
                            <td class="px-3 py-3 font-mono">{{ $item->item_code }}</td>
                            <td class="px-3 py-3">
                                <div class="font-medium">{{ $item->name }}</div>
                                @if ($item->description)
                                    <div class="mt-1 max-w-md text-xs text-zinc-500">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3">{{ $item->category?->name ?? '—' }}</td>
                            <td class="px-3 py-3">
                                {{ $item->tracking_type === 'individual' ? 'Per unit' : 'Kuantitas' }}
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ number_format($item->assets_count) }}</td>
                            <td class="px-3 py-3">
                                @if ($item->is_active)
                                    <flux:badge color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge color="zinc">Nonaktif</flux:badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-10 text-center text-zinc-500">Belum ada barang inventaris. Tambahkan kategori lalu daftarkan barang pertama.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $items->links() }}</div>
    </flux:card>
</div>
