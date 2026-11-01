<?php

namespace App\Http\Resources;

use App\Models\InventoryLoanDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryLoanDocument */
class InventoryDocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'loan_id' => $this->loan_id, 'document_type' => $this->document_type,
            'version' => $this->version, 'document_number' => $this->document_number,
            'status' => $this->status, 'issued_by' => $this->issued_by, 'issued_at' => $this->issued_at,
            'sha256' => $this->sha256, 'correction_reason' => $this->correction_reason,
            'pdf_url' => route('api.v1.inventory.documents.show', ['document' => $this->id]),
            'verification' => $this->whenLoaded('verification', fn () => $this->verification ? [
                'code' => $this->verification->code, 'status' => $this->verification->publicStatus(),
                'url' => route('reports.verify', ['code' => $this->verification->code]),
            ] : null),
        ];
    }
}
