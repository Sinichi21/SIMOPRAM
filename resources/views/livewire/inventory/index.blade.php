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
                <flux:button variant="outline" icon="qr-code" wire:click="$set('showAssetForm', true)">Tambah Unit Aset</flux:button>
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

    @if (count($lastCreatedAssetIds) > 0)
        <flux:card class="space-y-3">
            <flux:heading size="lg">Label QR Aset Baru</flux:heading>
            <flux:text size="sm">{{ count($lastCreatedAssetIds) }} unit berhasil didaftarkan. Cetak label QR per unit atau dalam satu lembar.</flux:text>
            @can('inventory.labels.print')
                <div class="flex flex-wrap gap-2">
                    @foreach (['a4' => 'Cetak A4', 'thermal58' => 'Thermal 58 mm', 'thermal80' => 'Thermal 80 mm'] as $format => $label)
                        <a class="rounded border px-3 py-2 text-sm underline" target="_blank" rel="noopener" href="{{ route('inventory.assets.batch-labels', ['format' => $format, 'ids' => implode(',', $lastCreatedAssetIds)]) }}">{{ $label }}</a>
                    @endforeach
                </div>
            @endcan
        </flux:card>
    @endif

    @if ($editingAssetId)
        <flux:card class="space-y-3">
            <flux:heading size="lg">Perbarui Lokasi / Kondisi Aset</flux:heading>
            <form wire:submit="saveAssetChange" class="grid gap-3 md:grid-cols-2">
                <flux:select label="Lokasi baru" wire:model="editingAssetLocationId" required>
                    @foreach ($locations as $location)
                        <flux:select.option value="{{ $location->id }}">{{ $location->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select label="Kondisi" wire:model="editingAssetCondition" required>
                    <flux:select.option value="good">Baik</flux:select.option>
                    <flux:select.option value="damaged">Rusak</flux:select.option>
                    <flux:select.option value="lost">Hilang</flux:select.option>
                </flux:select>
                <div class="md:col-span-2">
                    <flux:textarea label="Alasan perubahan (minimal 10 karakter)" wire:model="editingAssetReason" required />
                </div>
                <div class="md:col-span-2 flex gap-2">
                    <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
                    <flux:button type="button" variant="ghost" wire:click="$set('editingAssetId', null)">Batal</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    @if ($showAssetForm)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Daftarkan Unit Aset Individual</flux:heading>
            <form wire:submit="createAsset" class="grid gap-4 md:grid-cols-2">
                <flux:select label="Jenis barang" wire:model="assetItemId" required>
                    <flux:select.option value="">Pilih barang individual</flux:select.option>
                    @foreach ($individualItems as $individualItem)
                        <flux:select.option value="{{ $individualItem->id }}">{{ $individualItem->name }} ({{ $individualItem->item_code }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input label="Format / awalan kode aset" wire:model.live.debounce.400ms="assetCode" required maxlength="65" placeholder="KOMPAS" description="Contoh: KOMPAS akan menghasilkan KOMPAS-0001, KOMPAS-0002, dst." />
                <flux:input label="Jumlah unit yang didaftarkan" type="number" min="1" max="500" step="1" wire:model.live.debounce.400ms="assetQuantity" required />
                <flux:input label="Nomor urut terakhir (opsional, isi 0 jika baru)" type="number" min="0" max="999999999" step="1" wire:model.live.debounce.400ms="assetLastNumber" required />
                @if ($this->assetCodePreview)
                    <div class="md:col-span-2 rounded-lg border p-3 text-sm">
                        <div class="font-medium">Pratinjau {{ $this->assetCodePreview['count'] }} kode aset</div>
                        <div class="font-mono">{{ $this->assetCodePreview['first'] }} → {{ $this->assetCodePreview['last'] }}</div>
                        <div class="mt-1 text-xs text-zinc-500">Nomor akhir akan dihitung ulang saat disimpan untuk menghindari benturan.</div>
                    </div>
                @endif
                <div class="md:col-span-2 text-xs text-zinc-500">Sistem melanjutkan nomor terbesar yang sudah ada pada sekolah ini atau nomor terakhir yang Anda masukkan, mana yang lebih tinggi. Nomor seri manual hanya disimpan untuk pendaftaran satu unit.</div>
                <flux:select label="Pemilik" wire:model="assetOwnerId" required>
                    <flux:select.option value="">Pilih pemilik</flux:select.option>
                    @foreach ($owners as $owner)<flux:select.option value="{{ $owner->id }}">{{ $owner->display_name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select label="Lokasi" wire:model="assetLocationId" required>
                    <flux:select.option value="">Pilih lokasi</flux:select.option>
                    @foreach ($locations as $location)<flux:select.option value="{{ $location->id }}">{{ $location->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:input label="Nomor seri (opsional)" wire:model="assetSerialNumber" maxlength="120" />
                <flux:textarea label="Catatan (opsional)" wire:model="assetNotes" />
                <div class="md:col-span-2 flex gap-2">
                    <flux:button type="submit" variant="primary">Simpan Unit Aset</flux:button>
                    <flux:button type="button" variant="ghost" wire:click="$set('showAssetForm', false)">Batal</flux:button>
                </div>
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
                    <flux:select label="Gudep" wire:model="ownerScoutGroupId" required>
                    <flux:select.option value="">Pilih Gudep</flux:select.option>
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
                <flux:select label="Gudep terkait (opsional)" wire:model="locationScoutGroupId">
                    <flux:select.option value="">Lokasi umum sekolah</flux:select.option>
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
                <flux:select label="Kategori" wire:model="categoryId" required>
                    <flux:select.option value="">Pilih kategori</flux:select.option>
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
            <flux:callout icon="information-circle">Penyesuaian stok memasukkan jumlah akhir hasil pemeriksaan fisik, bukan nilai selisih. Catatan dan referensi dianjurkan untuk setiap transaksi.</flux:callout>
            <form wire:submit="recordMovement" class="grid gap-4 md:grid-cols-2">
                <flux:select label="Barang" wire:model="movementItemId" required>
                    <flux:select.option value="">Pilih barang</flux:select.option>
                    @foreach ($quantityItems as $item)<flux:select.option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select label="Pemilik" wire:model="movementOwnerId" required>
                    <flux:select.option value="">Pilih pemilik</flux:select.option>
                    @foreach ($owners as $owner)<flux:select.option value="{{ $owner->id }}">{{ $owner->display_name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select label="Lokasi" wire:model="movementLocationId" required>
                    <flux:select.option value="">Pilih lokasi</flux:select.option>
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
            <div><flux:heading size="lg">Daftar Barang</flux:heading><flux:text size="sm" class="text-zinc-500">Barang individual dapat dibuka untuk melihat kode unik setiap unit tanpa tabel terpisah.</flux:text></div>
            <div class="flex gap-2">
                @can('inventory.labels.print')
                    <a href="{{ route('inventory.assets.batch-labels', ['scope' => 'all', 'format' => 'a4']) }}" target="_blank" rel="noopener" class="rounded border px-3 py-2 text-sm">Cetak QR Semua Unit (A4)</a>
                @endcan
                <a href="{{ route('inventory.items.scan') }}" class="rounded border px-3 py-2 text-sm">Pindai QR</a>
                @can('inventory.stock.manage')<flux:button icon="arrows-right-left" variant="outline" wire:click="$set('showMovementForm', true)">Pergerakan Stok</flux:button>@endcan
                <div class="w-full sm:w-72"><flux:input icon="magnifying-glass" placeholder="Cari nama atau kode barang..." wire:model.live.debounce.300ms="search" /></div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b text-left"><th class="px-3 py-3 font-medium">Kode</th><th class="px-3 py-3 font-medium">Nama Barang</th><th class="px-3 py-3 font-medium">Kategori</th><th class="px-3 py-3 font-medium">Pelacakan</th><th class="px-3 py-3 font-medium text-right">Saldo / Aset</th><th class="px-3 py-3 font-medium">Status</th><th class="px-3 py-3 font-medium">Label</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr wire:key="inventory-item-{{ $item->id }}" class="border-b last:border-0">
                            <td class="px-3 py-3 font-mono">{{ $item->item_code }}</td>
                            <td class="px-3 py-3"><div class="font-medium">@if ($item->tracking_type === 'individual')<button type="button" class="text-left underline" wire:click="toggleItemUnits({{ $item->id }})">{{ $expandedItemId === $item->id ? '▾' : '▸' }} {{ $item->name }}</button>@else{{ $item->name }}@endif</div>@if ($item->description)<div class="mt-1 max-w-md text-xs text-zinc-500">{{ $item->description }}</div>@endif</td>
                            <td class="px-3 py-3">{{ $item->category?->name ?? '—' }}</td>
                            <td class="px-3 py-3">{{ $item->tracking_type === 'individual' ? 'Per unit' : 'Kuantitas' }}</td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ $item->tracking_type === 'individual' ? number_format($item->assets_count).' aset' : number_format((float) ($item->total_quantity ?? 0), 3).' '.$item->unit }}</td>
                            <td class="px-3 py-3">@if ($item->is_active)<flux:badge color="green">Aktif</flux:badge>@else<flux:badge color="zinc">Nonaktif</flux:badge>@endif</td>
                            <td class="px-3 py-3">@can('inventory.labels.print')@if ($item->tracking_type === 'individual')<a href="{{ route('inventory.assets.batch-labels', ['scope' => 'item', 'item_id' => $item->id, 'format' => 'a4']) }}" target="_blank" rel="noopener" class="text-xs underline">Cetak semua unit grup</a> @else<a href="{{ route('inventory.items.labels', ['item' => $item->id, 'format' => 'a4']) }}" target="_blank" rel="noopener" class="text-xs underline">Cetak A4</a> <a href="{{ route('inventory.items.labels', ['item' => $item->id, 'format' => 'thermal58']) }}" target="_blank" rel="noopener" class="text-xs underline">58 mm</a> <a href="{{ route('inventory.items.labels', ['item' => $item->id, 'format' => 'thermal80']) }}" target="_blank" rel="noopener" class="text-xs underline">80 mm</a>@endif @endcan</td>
                        </tr>
                        @if ($item->tracking_type === 'individual' && $expandedItemId === $item->id)
                            <tr wire:key="inventory-item-units-{{ $item->id }}" class="border-b bg-zinc-50/50 dark:bg-zinc-800/30">
                                <td colspan="7" class="p-4">
                                    <div class="mb-3 flex items-center justify-between">
                                        <strong>Unit {{ $item->name }} (maksimal 500 ditampilkan)</strong>
                                        @can('inventory.manage')
                                            <flux:button size="sm" icon="plus" wire:click="addUnitsToItem({{ $item->id }})">Tambah Unit</flux:button>
                                        @endcan
                                    </div>
                                    <div class="space-y-3">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b text-left"><th class="px-3 py-2">Kode aset</th><th class="px-3 py-2">Barang</th><th class="px-3 py-2">Pemilik / Lokasi</th><th class="px-3 py-2">Kondisi / Status</th><th class="px-3 py-2">Label QR</th></tr></thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr wire:key="inventory-asset-{{ $asset->id }}" class="border-b last:border-0">
                            <td class="px-3 py-2 font-mono">{{ $asset->asset_code }}</td>
                            <td class="px-3 py-2">{{ $asset->item?->name ?? '—' }}<div class="text-xs text-zinc-500">{{ $asset->serial_number }}</div></td>
                            <td class="px-3 py-2">{{ $asset->owner?->display_name ?? '—' }}<div class="text-xs text-zinc-500">{{ $asset->location?->name }}</div></td>
                            <td class="px-3 py-2">
                                {{ $asset->condition }} / {{ $asset->status }}
                                @can('inventory.manage')
                                    <button type="button" wire:click="editAsset({{ $asset->id }})" class="ml-2 text-xs underline">Ubah</button>
                                @endcan
                                @if ($asset->events->isNotEmpty())
                                    <details class="mt-1 text-xs">
                                        <summary class="cursor-pointer">Riwayat ({{ $asset->events->count() }})</summary>
                                        @foreach ($asset->events as $event)
                                            <div class="mt-1 border-t py-1">
                                                {{ $event->occurred_at?->format('d/m/Y H:i') }} — {{ $event->actor?->name ?? 'Akun tidak tersedia' }}
                                                <div>{{ $event->fromLocation?->name ?? '-' }} → {{ $event->toLocation?->name ?? '-' }}</div>
                                                <div>{{ $event->from_condition }} → {{ $event->to_condition }}</div>
                                                <div>{{ $event->reason }}</div>
                                            </div>
                                        @endforeach
                                    </details>
                                @endif
                            </td>
                            <td class="px-3 py-2">@can('inventory.labels.print')
                                <a class="underline text-xs" target="_blank" rel="noopener" href="{{ route('inventory.assets.labels', ['asset' => $asset->id, 'format' => 'a4']) }}">A4</a>
                                <a class="underline text-xs" target="_blank" rel="noopener" href="{{ route('inventory.assets.labels', ['asset' => $asset->id, 'format' => 'thermal58']) }}">58 mm</a>
                                <a class="underline text-xs" target="_blank" rel="noopener" href="{{ route('inventory.assets.labels', ['asset' => $asset->id, 'format' => 'thermal80']) }}">80 mm</a>
                            @endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-zinc-500">Belum ada unit aset individual.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
</div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="7" class="px-3 py-10 text-center text-zinc-500">Belum ada barang inventaris. Tambahkan kategori lalu daftarkan barang pertama.</td></tr>
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
