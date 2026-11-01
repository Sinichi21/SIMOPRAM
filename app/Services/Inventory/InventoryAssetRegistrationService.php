<?php

namespace App\Services\Inventory;

use App\Models\InventoryAsset;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\School;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryAssetRegistrationService
{
    /** @param array<string, mixed> $validated
     * @return array<int, int>
     */
    public function register(array $validated): array
    {
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId !== null, 409);
        $quantity = (int) $validated['assetQuantity'];
        $lastNumber = (int) $validated['assetLastNumber'];
        $prefix = $validated['assetCode'];
        if ($lastNumber + $quantity > 999999999) {
            throw ValidationException::withMessages([
                'assetQuantity' => 'Nomor urut melebihi batas yang diperbolehkan.',
            ]);
        }

        return DB::transaction(function () use ($schoolId, $validated, $prefix, $quantity, $lastNumber): array {
            // Lock the school row so concurrent batch registrations cannot generate overlapping codes.
            School::query()->whereKey($schoolId)->lockForUpdate()->firstOrFail();

            InventoryItem::query()->where('school_id', $schoolId)->where('tracking_type', 'individual')->where('is_active', true)->lockForUpdate()->findOrFail($validated['assetItemId']);
            InventoryOwner::query()->where('school_id', $schoolId)->where('is_active', true)->findOrFail($validated['assetOwnerId']);
            InventoryLocation::query()->where('school_id', $schoolId)->where('is_active', true)->findOrFail($validated['assetLocationId']);

            $existingCodes = InventoryAsset::withTrashed()->where('school_id', $schoolId)
                ->where('asset_code', 'like', $prefix.'-%')
                ->pluck('asset_code');
            $highest = $existingCodes->reduce(function (int $max, string $code) use ($prefix): int {
                $suffix = substr($code, strlen($prefix) + 1);

                return ctype_digit($suffix) ? max($max, (int) $suffix) : $max;
            }, 0);
            $startNumber = max($highest, $lastNumber) + 1;

            if ($startNumber + $quantity - 1 > 999999999) {
                throw ValidationException::withMessages([
                    'assetQuantity' => 'Rentang nomor urut telah mencapai batas.',
                ]);
            }

            $createdIds = [];
            for ($index = 0; $index < $quantity; $index++) {
                $number = $startNumber + $index;
                $asset = InventoryAsset::query()->create([
                    'school_id' => $schoolId,
                    'item_id' => $validated['assetItemId'],
                    'owner_id' => $validated['assetOwnerId'],
                    'location_id' => $validated['assetLocationId'],
                    'asset_code' => $prefix.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                    'serial_number' => $quantity === 1 ? ($validated['assetSerialNumber'] ?: null) : null,
                    'condition' => 'good',
                    'status' => 'available',
                    'notes' => $validated['assetNotes'] ?: null,
                ]);
                $createdIds[] = $asset->id;
            }

            return $createdIds;
        }, 3);
    }
}
