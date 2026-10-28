<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\InventoryStockBalance;
use App\Models\InventoryStockMovement;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryStockService
{
    public function recordMovement(
        int $itemId,
        int $ownerId,
        int $locationId,
        string $type,
        string|int|float $quantity,
        ?string $reference = null,
        ?string $notes = null,
        ?\DateTimeInterface $occurredAt = null,
    ): InventoryStockMovement {
        $schoolId = app(SchoolContext::class)->id();

        if (! $schoolId) {
            throw ValidationException::withMessages([
                'school' => 'Pilih sekolah aktif sebelum mengelola stok inventaris.',
            ]);
        }

        if (! in_array($type, ['receipt', 'issue', 'adjustment'], true)) {
            throw ValidationException::withMessages([
                'movement_type' => 'Jenis pergerakan stok tidak valid.',
            ]);
        }

        $amount = (float) $quantity;

        if (! is_finite($amount) || $amount <= 0 || round($amount, 3) !== $amount) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah harus lebih besar dari nol.',
            ]);
        }

        return DB::transaction(function () use (
            $schoolId, $itemId, $ownerId, $locationId, $type, $amount,
            $reference, $notes, $occurredAt
        ): InventoryStockMovement {
            $item = InventoryItem::query()->where('school_id', $schoolId)->lockForUpdate()->findOrFail($itemId);
            $owner = InventoryOwner::query()->where('school_id', $schoolId)->findOrFail($ownerId);
            $location = InventoryLocation::query()->where('school_id', $schoolId)->findOrFail($locationId);

            if (! $item->category()->where('school_id', $schoolId)->exists()) {
                throw ValidationException::withMessages([
                    'item_id' => 'Kategori barang tidak sesuai dengan sekolah aktif.',
                ]);
            }

            if ($owner->owner_type === 'school' && $owner->scout_group_id !== null) {
                throw ValidationException::withMessages([
                    'owner_id' => 'Kepemilikan sekolah tidak boleh menunjuk ke Gudep tertentu.',
                ]);
            }

            if ($owner->owner_type === 'gudep') {
                abort_unless($owner->scout_group_id, 422, 'Pemilik Gudep harus memiliki referensi Gudep.');
                \App\Models\ScoutGroup::query()
                    ->where('school_id', $schoolId)
                    ->findOrFail($owner->scout_group_id);
            }

            if ($location->scout_group_id) {
                \App\Models\ScoutGroup::query()
                    ->where('school_id', $schoolId)
                    ->findOrFail($location->scout_group_id);
            }

            if ($item->tracking_type !== 'quantity') {
                throw ValidationException::withMessages([
                    'item_id' => 'Barang yang dilacak per unit harus dikelola melalui data aset, bukan saldo kuantitas.',
                ]);
            }

            if (! $item->is_active || ! $owner->is_active || ! $location->is_active) {
                throw ValidationException::withMessages([
                    'item_id' => 'Barang, pemilik, dan lokasi harus aktif.',
                ]);
            }

            if ($owner->owner_type === 'gudep' && ! $owner->scout_group_id) {
                throw ValidationException::withMessages([
                    'owner_id' => 'Pemilik Gudep harus memiliki referensi Gudep.',
                ]);
            }

            if ($owner->scout_group_id && $location->scout_group_id && $owner->scout_group_id !== $location->scout_group_id) {
                throw ValidationException::withMessages([
                    'location_id' => 'Lokasi harus sesuai dengan Gudep pemilik.',
                ]);
            }

            $balance = InventoryStockBalance::query()
                ->where('school_id', $schoolId)
                ->where('item_id', $item->id)
                ->where('owner_id', $owner->id)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryStockBalance::query()->create([
                    'school_id' => $schoolId,
                    'item_id' => $item->id,
                    'owner_id' => $owner->id,
                    'location_id' => $location->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                ]);
                $balance = InventoryStockBalance::query()->whereKey($balance->id)->lockForUpdate()->firstOrFail();
            }

            $current = (float) $balance->quantity_on_hand;
            $next = match ($type) {
                'receipt' => $current + $amount,
                'issue' => $current - $amount,
                'adjustment' => $amount,
            };

            if ($next < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok tidak mencukupi untuk pengeluaran tersebut.',
                ]);
            }

            $movementQuantity = $type === 'adjustment' ? abs($next - $current) : $amount;

            if ($type === 'adjustment' && $movementQuantity == 0.0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah hasil penyesuaian sama dengan saldo saat ini; tidak ada perubahan untuk dicatat.',
                ]);
            }

            $balance->quantity_on_hand = $next;
            $balance->save();

            return InventoryStockMovement::query()->create([
                'school_id' => $schoolId,
                'item_id' => $item->id,
                'owner_id' => $owner->id,
                'location_id' => $location->id,
                'movement_type' => $type,
                'quantity' => $movementQuantity,
                'balance_after' => $next,
                'reference' => $reference,
                'notes' => $notes,
                'actor_id' => auth()->id(),
                'occurred_at' => $occurredAt ?? now(),
            ]);
        }, 3);
    }
}
