<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
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
            'title' => $this->title,
            'activity_type' => $this->activity_type,
            'routine_session_no' => $this->routine_session_no,
            'description' => $this->description,
            'location' => $this->location,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'status' => $this->status,
            'academic_year' => $this->whenLoaded('academicYear', fn () => ['id' => $this->academicYear->id, 'name' => $this->academicYear->name]),
            'coaches' => $this->whenLoaded('coaches', fn () => $this->coaches->map(fn ($coach) => ['id' => $coach->id, 'name' => $coach->name])),
            'attendance_sessions_count' => $this->whenCounted('attendanceSessions'),
        ];
    }
}
