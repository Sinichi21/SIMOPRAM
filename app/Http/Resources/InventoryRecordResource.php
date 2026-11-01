<?php

namespace App\Http\Resources;

use App\Models\InventoryLoanEvidence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryRecordResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $data = $this->resource->only([
            'id', 'name', 'description', 'display_name', 'is_active', 'owner_type', 'scout_group_id',
            'location_type', 'category_id', 'item_code', 'tracking_type', 'unit', 'minimum_stock',
            'item_id', 'owner_id', 'location_id', 'asset_id', 'asset_code', 'serial_number', 'status', 'condition',
            'purchased_at', 'notes', 'loan_id', 'loan_item_id', 'loan_event_id', 'event_type', 'recorded_by',
            'recording_method', 'recorded_at', 'occurred_at', 'actor_id', 'movement_type', 'reference',
            'quantity', 'balance_after', 'quantity_on_hand', 'quantity_reserved', 'quantity_requested',
            'quantity_handed_over', 'quantity_returned', 'quantity_missing', 'condition_out', 'condition_in',
            'condition_notes', 'from_location_id', 'to_location_id', 'from_condition', 'to_condition', 'reason',
            'mime_type', 'size_bytes', 'uploaded_by', 'created_at', 'updated_at',
        ]);
        foreach ($data as $key => $value) {
            if ($value !== null && (str_starts_with($key, 'quantity') || in_array($key, ['balance_after', 'minimum_stock'], true))) {
                $data[$key] = (float) $value;
            }
        }
        foreach (['item', 'owner', 'location', 'category', 'fromLocation', 'toLocation'] as $relation) {
            if ($this->resource->relationLoaded($relation)) {
                $data[$relation] = $this->resource->getRelation($relation)?->only(['id', 'name', 'display_name', 'item_code', 'unit', 'is_active']);
            }
        }
        foreach (['actor', 'recorder'] as $relation) {
            if ($this->resource->relationLoaded($relation)) {
                $data[$relation] = $this->resource->getRelation($relation)?->only(['id', 'name']);
            }
        }
        if ($this->resource instanceof InventoryLoanEvidence) {
            $data['url'] = route('api.v1.inventory.evidence.show', ['evidence' => $this->resource->getKey()]);
        }
        if ($this->resource->relationLoaded('evidence')) {
            $data['evidence'] = self::collection($this->resource->getRelation('evidence'));
        }

        return $data;
    }
}
