<?php

namespace App\Services\Inventory;

use App\Models\InventoryAsset;
use App\Models\InventoryAssetEvent;
use App\Models\InventoryLocation;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryAssetService
{
    public function updateLocationAndCondition(int $assetId, int $locationId, string $condition, string $reason): InventoryAsset
    {
        abort_unless(auth()->user()?->can('inventory.manage'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['reason' => 'Alasan perubahan harus 10–2000 karakter.']);
        }

        if (! in_array($condition, ['good', 'damaged', 'lost'], true)) {
            throw ValidationException::withMessages(['condition' => 'Kondisi aset tidak valid.']);
        }

        return DB::transaction(function () use ($assetId, $locationId, $condition, $reason, $schoolId): InventoryAsset {
            $asset = InventoryAsset::query()->where('school_id', $schoolId)->lockForUpdate()->findOrFail($assetId);
            $location = InventoryLocation::query()->where('school_id', $schoolId)->where('is_active', true)->findOrFail($locationId);

            if ($asset->location_id === $location->id && $asset->condition === $condition) {
                throw ValidationException::withMessages(['condition' => 'Tidak ada perubahan lokasi atau kondisi.']);
            }

            InventoryAssetEvent::query()->create([
                'school_id' => $schoolId,
                'asset_id' => $asset->id,
                'actor_id' => auth()->id(),
                'from_location_id' => $asset->location_id,
                'to_location_id' => $location->id,
                'from_condition' => $asset->condition,
                'to_condition' => $condition,
                'reason' => $reason,
                'occurred_at' => now(),
            ]);

            $asset->update(['location_id' => $location->id, 'condition' => $condition]);

            return $asset->refresh();
        }, 3);
    }
}
