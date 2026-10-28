<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Inventaris</flux:heading>
            <flux:subheading>Kelola barang, kepemilikan, lokasi penyimpanan, dan pergerakan stok.</flux:subheading>
        </div>
        @can('inventory.manage')
            <div class="flex flex-wrap gap-2">
                <flux:button variant="outline" icon="tag" wire:click="$set('showCategoryForm', true)">Kategori</flux:button>
                <flux:button variant="outline" icon="users" wire:click="$set('showOwnerForm', true)">Pemilik</flux:button>
                <flux:button variant="outline" icon="map-pin" wire:click="$set('showLocationForm', true)">Lokasi</flux:button>
                <flux:button variant="primary" icon="plus" wire:click="$set('showItemForm', true)">Tambah Barang</flux:button>
            </div>
        @endcan
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </flux:callout>
    @endif

    @if ($showCategoryForm)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Kategori Baru</flux:heading>
            <form wire:submit="createCategory" class="space-y-4">
                <flux:input label="Nama kategori" wire:model="categoryName" required maxlength="100" />
                <flux:textarea label="Deskripsi (opsional)" wire:model="categoryDescription" />
                <div class="flex gap-2"><flux:button type="submit" variant="primary">Simpan Kategori</flux:button><flux:button type="button" variant="ghost" wire:click="$set('showCategoryForm', false)">Batal</flux:button></div>
            </form>
        </flux:card>
    @endif

    @if ($showOwnerForm)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Pemilik Inventaris</flux:heading>
            <form wire:submit="createOwner" class="grid gap-4 md:grid-cols-2">
                <flux:select label="Jenis pemilik" wire:model.live="ownerType" required>
                    <flux:select.option value="school">Sekolah</flux:select.option>
                    <flux:select.option value="gudep">Gugus Depan (Gudep)</flux:select.option>
                </flux:select>
                @if ($ownerType === 'gudep')
                    <flux:select label="Gudep" wire:model="ownerScoutGroupId" placeholder="Pilih Gudep" required>
                        @foreach ($groups as $group)<flux:select.option value="{{ $group->id }}">{{ $group->name }}</flux:select.option>@endforeach
                    </flux:select>
                @endif
                <flux:input label="Nama tampilan pemilik" wire:model="ownerDisplayName" required maxlength="150" placeholder="Contoh: Inventaris Gudep Putra" />
                <div class="md:col-span-2 flex gap-2"><flux:button type="submit" variant="primary">Simpan Pemilik</flux:button><flux:button type="button" variant="ghost" wire:click="$set('showOwnerForm', false)">Batal</flux:button></div>
            </form>
        </flux:card>
    @endif

    @if ($showLocationForm)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Lokasi Penyimpanan</flux:heading>
            <form wire:submit="createLocation" class="grid gap-4 md:grid-cols-2">
                <flux:input label="Nama lokasi" wire:model="locationName" required maxlength="120" placeholder="Contoh: Gudang Pramuka" />
                <flux:select label="Jenis lokasi" wire:model="locationType" required>
                    <flux:select.option value="storage">Gudang</flux:select.option>
                    <flux:select.option value="room">Ruangan</flux:select.option>
                    <flux:select.option value="vehicle">Kendaraan</flux:select.option>
                    <flux:select.option value="other">Lainnya</flux:select.option>
                </flux:select>
                <flux:select label="Gudep terkait (opsional)" wire:model="locationScoutGroupId" placeholder="Lokasi umum sekolah">
                    @foreach ($groups as $group)<flux:select.option value="{{ $group->id }}">{{ $group->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:textarea label="Deskripsi (opsional)" wire:model="locationDescription" />
                <div class="md:col-span-2 flex gap-2"><flux:button type="submit" variant="primary">Simpan Lokasi</flux:button><flux:button type="button" variant="ghost" wire:click="$set('showLocationForm', false)">Batal</flux:button></div>
            </form>
        </flux:card>
    @endif

    @if ($showItemForm)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Barang Baru</flux:heading>
            <form wire:submit="createItem" class="grid gap-4 md:grid-cols-2">
                <flux:select label="Kategori" wire:model="categoryId" placeholder="Pilih kategori" required>
                    @foreach ($categories as $category)<flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:input label="Kode barang" wire:model="itemCode" required maxlength="60" placeholder="CONTOH: TENDA-001" />
                <flux:input label="Nama barang" wire:model="itemName" required maxlength="180" />
                <flux:select label="Metode pelacakan" wire:model="trackingType" required>
                    <flux:select.option value="quantity">Stok berdasarkan kuantitas</flux:select.option>
                    <flux:select.option value="individual">Aset per unit (barcode/QR)</flux:select.option>
                </flux:select>
                <flux:input label="Satuan" wire:model="unit" required maxlength="30" placeholder="unit, buah, meter..." />
                <flux:input label="Batas minimum stok" type="number" min="0" step="0.001" wire:model="minimumStock" required />
                <div class="md:col-span-2"><flux:textarea label="Deskripsi (opsional)" wire:model="itemDescription" /></div>
                <div class="md:col-span-2 flex gap-2"><flux:button type="submit" variant="primary">Simpan Barang</flux:button><flux:button type="button" variant="ghost" wire:click="$set('showItemForm', false)">Batal</flux:button></div>
            </form>
        </flux:card>
    @endif

    @if ($showMovementForm)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Catat Pergerakan Stok</flux:heading>
            <flux:callout icon="info">Penyesuaian stok memasukkan jumlah akhir hasil pemeriksaan fisik, bukan nilai selisih. Catatan dan referensi dianjurkan untuk setiap transaksi.</flux:callout>
            <form wire:submit="recordMovement" class="grid gap-4 md:grid-cols-2">
                <flux:select label="Barang" wire:model="movementItemId" placeholder="Pilih barang" required>
                    @foreach ($quantityItems as $item)<flux:select.option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select label="Pemilik" wire:model="movementOwnerId" placeholder="Pilih pemilik" required>
                    @foreach ($owners as $owner)<flux:select.option value="{{ $owner->id }}">{{ $owner->display_name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select label="Lokasi" wire:model="movementLocationId" placeholder="Pilih lokasi" required>
                    @foreach ($locations as $location)<flux:select.option value="{{ $location->id }}">{{ $location->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select label="Jenis pergerakan" wire:model="movementType" required>
                    <flux:select.option value="receipt">Stok masuk</flux:select.option>
                    <flux:select.option value="issue">Stok keluar</flux:select.option>
                    <flux:select.option value="adjustment">Penyesuaian — jumlah akhir</flux:select.option>
                </flux:select>
                <flux:input label="{{ $movementType === 'adjustment' ? 'Jumlah akhir setelah pemeriksaan' : 'Jumlah' }}" type="number" min="0.001" step="0.001" wire:model="movementQuantity" required />
                <flux:input label="Nomor referensi (opsional)" wire:model="movementReference" maxlength="100" />
                <div class="md:col-span-2"><flux:textarea label="Catatan" wire:model="movementNotes" /></div>
                <div class="md:col-span-2 flex gap-2"><flux:button type="submit" variant="primary">Catat Stok</flux:button><flux:button type="button" variant="ghost" wire:click="$set('showMovementForm', false)">Batal</flux:button></div>
            </form>
        </flux:card>
    @endif

    <flux:card class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><flux:heading size="lg">Daftar Barang</flux:heading><flux:text size="sm" class="text-zinc-500">Saldo kuantitas dan jumlah aset individual ditampilkan terpisah.</flux:text></div>
            <div class="flex gap-2">
                @can('inventory.stock.manage')<flux:button icon="arrows-right-left" variant="outline" wire:click="$set('showMovementForm', true)">Pergerakan Stok</flux:button>@endcan
                <div class="w-full sm:w-72"><flux:input icon="magnifying-glass" placeholder="Cari nama atau kode barang..." wire:model.live.debounce.300ms="search" /></div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b text-left"><th class="px-3 py-3 font-medium">Kode</th><th class="px-3 py-3 font-medium">Nama Barang</th><th class="px-3 py-3 font-medium">Kategori</th><th class="px-3 py-3 font-medium">Pelacakan</th><th class="px-3 py-3 font-medium text-right">Saldo / Aset</th><th class="px-3 py-3 font-medium">Status</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr wire:key="inventory-item-{{ $item->id }}" class="border-b last:border-0">
                            <td class="px-3 py-3 font-mono">{{ $item->item_code }}</td>
                            <td class="px-3 py-3"><div class="font-medium">{{ $item->name }}</div>@if ($item->description)<div class="mt-1 max-w-md text-xs text-zinc-500">{{ $item->description }}</div>@endif</td>
                            <td class="px-3 py-3">{{ $item->category?->name ?? '—' }}</td>
                            <td class="px-3 py-3">{{ $item->tracking_type === 'individual' ? 'Per unit' : 'Kuantitas' }}</td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ $item->tracking_type === 'individual' ? number_format($item->assets_count).' aset' : number_format((float) ($item->total_quantity ?? 0), 3).' '.$item->unit }}</td>
                            <td class="px-3 py-3">@if ($item->is_active)<flux:badge color="green">Aktif</flux:badge>@else<flux:badge color="zinc">Nonaktif</flux:badge>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-10 text-center text-zinc-500">Belum ada barang inventaris. Tambahkan kategori lalu daftarkan barang pertama.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $items->links() }}</div>
    </flux:card>

    <div class="grid gap-4 xl:grid-cols-2">
        <flux:card class="space-y-3">
            <flux:heading size="lg">Pemilik Inventaris</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="px-3 py-2 font-medium">Nama Pemilik</th><th class="px-3 py-2 font-medium">Jenis</th><th class="px-3 py-2 font-medium">Gudep</th></tr></thead>
                    <tbody>
                        @forelse ($owners as $owner)
                            <tr wire:key="inventory-owner-{{ $owner->id }}" class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $owner->display_name }}</td>
                                <td class="px-3 py-2">{{ $owner->owner_type === 'gudep' ? 'Gudep' : 'Sekolah' }}</td>
                                <td class="px-3 py-2">{{ $owner->scoutGroup?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-6 text-center text-zinc-500">Belum ada pemilik inventaris.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
        <flux:card class="space-y-3">
            <flux:heading size="lg">Lokasi Penyimpanan</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="px-3 py-2 font-medium">Nama Lokasi</th><th class="px-3 py-2 font-medium">Jenis</th><th class="px-3 py-2 font-medium">Gudep</th></tr></thead>
                    <tbody>
                        @forelse ($locations as $location)
                            <tr wire:key="inventory-location-{{ $location->id }}" class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $location->name }}</td>
                                <td class="px-3 py-2">{{ ['storage' => 'Gudang', 'room' => 'Ruangan', 'vehicle' => 'Kendaraan', 'other' => 'Lainnya'][$location->location_type] ?? $location->location_type }}</td>
                                <td class="px-3 py-2">{{ $location->scoutGroup?->name ?? 'Umum' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-6 text-center text-zinc-500">Belum ada lokasi penyimpanan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    </div>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Pergerakan Stok Terbaru</flux:heading>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b text-left"><th class="px-3 py-3 font-medium">Waktu</th><th class="px-3 py-3 font-medium">Barang</th><th class="px-3 py-3 font-medium">Pemilik / Lokasi</th><th class="px-3 py-3 font-medium">Jenis</th><th class="px-3 py-3 font-medium text-right">Jumlah</th><th class="px-3 py-3 font-medium text-right">Saldo akhir</th></tr></thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr wire:key="inventory-movement-{{ $movement->id }}" class="border-b last:border-0">
                            <td class="px-3 py-3 whitespace-nowrap">{{ $movement->occurred_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-3">{{ $movement->item?->name ?? 'Barang dihapus' }}<div class="text-xs text-zinc-500">{{ $movement->item?->item_code }}</div></td>
                            <td class="px-3 py-3">{{ $movement->owner?->display_name ?? '—' }}<div class="text-xs text-zinc-500">{{ $movement->location?->name }}</div></td>
                            <td class="px-3 py-3">{{ ['receipt' => 'Masuk', 'issue' => 'Keluar', 'adjustment' => 'Penyesuaian'][$movement->movement_type] ?? $movement->movement_type }}</td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ number_format((float) $movement->quantity, 3) }}</td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ number_format((float) $movement->balance_after, 3) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-zinc-500">Belum ada transaksi stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
