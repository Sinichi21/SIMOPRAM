<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Activity;
use App\Support\SchoolContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivitySaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $schoolId = app(SchoolContext::class)->id();
        $academicYearId = $this->input('academic_year_id');
        if ($academicYearId === null && $this->route('activityId')) {
            $academicYearId = Activity::query()->whereKey($this->route('activityId'))->value('academic_year_id');
        }

        return [
            'academic_year_id' => ['sometimes', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'semester_id' => ['nullable', 'integer', Rule::exists('semesters', 'id')->where('school_id', $schoolId)->where('academic_year_id', $academicYearId)],
            'parent_activity_id' => ['nullable', 'integer', Rule::exists('activities', 'id')->where('school_id', $schoolId)->whereNull('parent_activity_id')],
            'title' => ['required', 'string', 'max:200'],
            'activity_type' => ['required', Rule::in([
                'regular', 'training', 'ceremony', 'camp', 'competition', 'service', 'other',
            ])],
            'routine_session_no' => [
                Rule::requiredIf($this->input('activity_type') === 'regular'),
                'nullable',
                'integer',
                'min:1',
                'max:10',
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'completed'])],
            'is_public' => ['sometimes', 'boolean'],
            'coach_ids' => ['sometimes', 'array'],
            'coach_ids.*' => ['integer', Rule::exists('coaches', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'scout_level_ids' => ['sometimes', 'array'],
            'scout_level_ids.*' => ['integer', Rule::exists('scout_levels', 'id')],
        ];
    }
}
