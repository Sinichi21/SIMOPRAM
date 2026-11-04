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
        $student = $this->student;

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'classroom' => $student->enrollments
                    ->first()?->classroom?->name,
            ],
            'attendance' => $this->whenLoaded(
                'attendance',
                fn () => $this->attendance
                    ? new AttendanceResource($this->attendance)
                    : null
            ),
        ];
    }
}
