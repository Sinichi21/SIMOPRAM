<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schoolId = (int) $this->attributes->get('api_school')->id;

        return [
            'search' => ['sometimes', 'string', 'max:100'],
            'academic_year_id' => ['sometimes', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'classroom_id' => ['sometimes', 'integer', Rule::exists('classrooms', 'id')->where('school_id', $schoolId)],
            'scout_level_id' => ['sometimes', 'integer', Rule::exists('scout_levels', 'id')],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'graduated', 'transferred'])],
            'gender' => ['sometimes', Rule::in(['L', 'P'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
