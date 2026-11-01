<?php

namespace App\Http\Resources;

use App\Models\InventoryAsset;
use Illuminate\Http\Request;

/** @mixin InventoryAsset */
class InventoryAssetResource extends InventoryRecordResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'qr_payload' => route('inventory.assets.verify', ['school' => $this->school_id, 'asset' => $this->id]),
            'events' => InventoryRecordResource::collection($this->whenLoaded('events')),
        ]);
    }
}
