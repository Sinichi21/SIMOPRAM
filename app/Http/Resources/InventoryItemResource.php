<?php

namespace App\Http\Resources;

use App\Models\InventoryItem;
use Illuminate\Http\Request;

/** @mixin InventoryItem */
class InventoryItemResource extends InventoryRecordResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'total_quantity' => (float) ($this->total_quantity ?? 0),
            'assets_count' => $this->whenCounted('assets'),
            'stock_balances' => InventoryRecordResource::collection($this->whenLoaded('stockBalances')),
            'qr_payload' => route('inventory.items.verify', ['school' => $this->school_id, 'item' => $this->id]),
        ]);
    }
}
