<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Student */
class StudentResource extends JsonResource
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
            'nis' => $this->nis,
            'nisn' => $this->nisn,
            'name' => $this->name,
            'gender' => $this->gender,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date,
            'phone' => $this->phone,
            'parent_phone' => $this->parent_phone,
            'address' => $this->address,
            'joined_at' => $this->joined_at,
            'status' => $this->status,
            'current_enrollment' => $this->whenLoaded('enrollments', function (): ?array {
                $enrollment = $this->enrollments->first();
                if (! $enrollment) {
                    return null;
                }

                return [
                    'academic_year' => ['id' => $enrollment->academicYear->id, 'name' => $enrollment->academicYear->name],
                    'classroom' => ['id' => $enrollment->classroom->id, 'name' => $enrollment->classroom->name],
                    'status' => $enrollment->status,
                ];
            }),
            'current_scout_level' => $this->whenLoaded('scoutLevelHistories', function (): ?array {
                $history = $this->scoutLevelHistories->first();
                if (! $history) {
                    return null;
                }

                return ['id' => $history->scoutLevel->id, 'name' => $history->scoutLevel->name];
            }),
        ];
    }
}
