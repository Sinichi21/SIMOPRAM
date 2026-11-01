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
            'is_public' => $this->is_public,
            'published_at' => $this->published_at,
            'parent_activity_id' => $this->parent_activity_id,
            'academic_year' => $this->whenLoaded('academicYear', fn () => ['id' => $this->academicYear->id, 'name' => $this->academicYear->name]),
            'semester' => $this->whenLoaded('semester', fn () => $this->semester ? ['id' => $this->semester->id, 'name' => $this->semester->name] : null),
            'coaches' => $this->whenLoaded('coaches', fn () => $this->coaches->map(fn ($coach) => ['id' => $coach->id, 'name' => $coach->name])),
            'scout_levels' => $this->whenLoaded('scoutLevels', fn () => $this->scoutLevels->map(fn ($level) => ['id' => $level->id, 'name' => $level->name])),
            'journal' => $this->when($request->user()?->can('journals.view') && $this->resource->relationLoaded('journal'), fn () => $this->journal ? [
                'id' => $this->journal->id,
                'objective' => $this->journal->objective,
                'material' => $this->journal->material,
                'activity_description' => $this->journal->activity_description,
                'result' => $this->journal->result,
                'evaluation' => $this->journal->evaluation,
                'follow_up' => $this->journal->follow_up,
                'notes' => $this->journal->notes,
                'status' => $this->journal->status,
            ] : null),
            'attendance_sessions_count' => $this->whenCounted('attendanceSessions'),
        ];
    }
}
