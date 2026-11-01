<?php

namespace App\Http\Resources;

use App\Models\InventoryLoan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryLoan */
class InventoryLoanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return array_merge($this->resource->only([
            'id', 'loan_number', 'borrower_type', 'borrower_user_id', 'borrower_scout_group_id',
            'borrower_name', 'borrower_organization', 'borrower_contact', 'purpose', 'notes',
            'requested_by', 'approved_by', 'status', 'requested_at', 'approved_at',
            'actual_handover_at', 'actual_return_at', 'created_at', 'updated_at',
        ]), [
            'items' => InventoryRecordResource::collection($this->whenLoaded('items')),
            'events' => InventoryRecordResource::collection($this->whenLoaded('events')),
            'documents' => InventoryDocumentResource::collection($this->whenLoaded('documents')),
        ]);
    }
}
