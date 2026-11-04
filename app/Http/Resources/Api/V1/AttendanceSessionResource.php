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
            'participant_scope' => $this->participant_scope,
            'participant_scope_id' => $this->participant_scope_id,
            'open_at' => $this->open_at?->format('Y-m-d H:i:s'),
            'late_after' => $this->late_after?->format('Y-m-d H:i:s'),
            'close_at' => $this->close_at?->format('Y-m-d H:i:s'),
            'is_active' => $this->is_active,
            'allow_manual' => $this->allow_manual,
            'allow_self_checkin' => $this->allow_self_checkin,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_m' => $this->radius_m,
            'max_accuracy_m' => $this->max_accuracy_m,
            'participants_count' => $this->whenCounted('participants'),
        ];
    }
}
