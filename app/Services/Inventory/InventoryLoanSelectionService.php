<?php

namespace App\Services\Inventory;

use App\Models\InventoryAsset;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\InventoryStockBalance;
use App\Support\SchoolContext;
use Illuminate\Validation\ValidationException;

class InventoryLoanSelectionService
{
    /** @return array{item: InventoryItem, asset: ?InventoryAsset} */
    public function resolve(string $code): array
    {
        $code = trim($code);
        $schoolId = $this->schoolId();
        $asset = null;
        $item = null;

        if (filter_var($code, FILTER_VALIDATE_URL)) {
            $path = parse_url($code, PHP_URL_PATH);
            if (parse_url($code, PHP_URL_HOST) === parse_url(url('/'), PHP_URL_HOST)
                && is_string($path) && preg_match('~^/inventaris/(verifikasi|verifikasi-aset)/(\d+)/(\d+)$~', $path, $matches)
                && (int) $matches[2] === $schoolId) {
                if ($matches[1] === 'verifikasi-aset') {
                    $asset = InventoryAsset::query()->where('school_id', $schoolId)->find($matches[3]);
                } else {
                    $item = InventoryItem::query()->where('school_id', $schoolId)->where('is_active', true)->find($matches[3]);
                }
            }
        } else {
            $asset = InventoryAsset::query()->where('school_id', $schoolId)->where('asset_code', $code)->first();
            if (! $asset) {
                $item = InventoryItem::query()->where('school_id', $schoolId)->where('is_active', true)->where('item_code', $code)->first();
            }
        }

        if ($asset) {
            $item = InventoryItem::query()->where('school_id', $schoolId)->where('is_active', true)->find($asset->item_id);
            if ($asset->status !== 'available' || $asset->condition !== 'good') {
                throw ValidationException::withMessages(['itemCode' => 'Unit aset tidak tersedia atau kondisinya tidak baik.']);
            }
        }
        if (! $item) {
            throw ValidationException::withMessages(['itemCode' => 'Kode barang / QR tidak ditemukan di sekolah aktif.']);
        }

        return ['item' => $item, 'asset' => $asset];
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     * @return array<int, array<string, mixed>>
     */
    public function add(array $cart, InventoryItem $item, string|int|float $quantity, ?int $assetId = null): array
    {
        $schoolId = $this->schoolId();
        abort_unless((int) $item->school_id === $schoolId && $item->is_active, 404);
        $remaining = (int) round((float) $quantity * 1000);
        $pending = [];
        if ($item->tracking_type === 'individual') {
            if ($remaining % 1000 !== 0 || ($assetId && $remaining !== 1000)) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah unit harus bulat; satu untuk kode unit tertentu.']);
            }
            $sources = InventoryAsset::query()->where('school_id', $schoolId)->where('item_id', $item->id)
                ->where('status', 'available')->where('condition', 'good')
                ->when($assetId, fn ($query) => $query->whereKey($assetId))
                ->whereNotIn('id', array_filter(array_column($cart, 'asset_id')))
                ->orderBy('id')->limit(500)->get();
        } else {
            $sources = InventoryStockBalance::query()->where('school_id', $schoolId)->where('item_id', $item->id)
                ->whereColumn('quantity_on_hand', '>', 'quantity_reserved')->orderBy('id')->get();
        }
        $sources->load(['owner', 'location']);
        foreach ($sources as $source) {
            $owner = $source->owner;
            $location = $source->location;
            if (! $owner instanceof InventoryOwner || ! $location instanceof InventoryLocation
                || ! $owner->is_active || ! $location->is_active
                || $owner->school_id !== $schoolId || $location->school_id !== $schoolId) {
                continue;
            }
            $asset = $source instanceof InventoryAsset ? $source : null;
            $key = implode(':', [$item->id, $source->owner_id, $source->location_id, $asset ? $asset->id : 'stock']);
            $existing = collect($cart)->firstWhere('key', $key);
            $available = $asset ? 1000 : (int) round(((float) $source->quantity_on_hand - (float) $source->quantity_reserved - (float) ($existing['quantity'] ?? 0)) * 1000);
            $allocated = min($remaining, max(0, $available));
            if ($allocated === 0) {
                continue;
            }
            $pending[] = [
                'key' => $key, 'item_id' => $item->id, 'owner_id' => $source->owner_id,
                'location_id' => $source->location_id, 'asset_id' => $asset?->id,
                'quantity' => $allocated / 1000, 'name' => $item->name,
                'asset_code' => $asset?->asset_code,
                'owner_name' => $owner->display_name, 'location_name' => $location->name,
            ];
            $remaining -= $allocated;
            if ($remaining === 0) {
                break;
            }
        }
        if ($remaining > 0) {
            throw ValidationException::withMessages(['quantity' => 'Stok / unit tersedia tidak mencukupi (termasuk barang di keranjang).']);
        }
        if (count($cart) + count($pending) > 500) {
            throw ValidationException::withMessages(['cart' => 'Keranjang maksimal 500 baris.']);
        }
        foreach ($pending as $line) {
            $index = array_search($line['key'], array_column($cart, 'key'), true);
            if ($index === false) {
                $cart[] = $line;
            } else {
                $cart[$index]['quantity'] = round($cart[$index]['quantity'] + $line['quantity'], 3);
            }
        }

        return $cart;
    }

    private function schoolId(): int
    {
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId !== null, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $schoolId;
    }
}
