<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceSessionResource extends JsonResource
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
            'activity_id' => $this->activity_id,
            'name' => $this->name,
            'open_at' => $this->open_at,
            'late_after' => $this->late_after,
            'close_at' => $this->close_at,
            'is_active' => $this->is_active,
            'allow_manual' => $this->allow_manual,
            'allow_self_checkin' => $this->allow_self_checkin,
            'participants_count' => $this->whenCounted('participants'),
        ];
    }
}
