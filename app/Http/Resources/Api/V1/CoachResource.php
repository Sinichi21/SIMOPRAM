<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Coach */
class CoachResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nip' => $this->nip,
            'name' => $this->name,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'position' => $this->position,
            'certificate_number' => $this->certificate_number,
            'is_active' => $this->is_active,
            'has_account' => $this->user_id !== null,
        ];
    }
}
