<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'student' => ['id' => $this->student->id, 'name' => $this->student->name, 'nis' => $this->student->nis, 'nisn' => $this->student->nisn],
            'attendance' => $this->whenLoaded('attendance', fn () => $this->attendance ? new AttendanceResource($this->attendance) : null),
        ];
    }
}
