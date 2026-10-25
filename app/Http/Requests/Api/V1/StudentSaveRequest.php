<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $schoolId = (int) $this->attributes->get('api_school')->id;

        return [
            'nis' => ['required', 'string', 'max:50', Rule::unique('students', 'nis')->where('school_id', $schoolId)->ignore($this->route('studentId'))],
            'nisn' => ['nullable', 'string', 'max:50', Rule::unique('students', 'nisn')->where('school_id', $schoolId)->ignore($this->route('studentId'))],
            'name' => ['required', 'string', 'max:150'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'joined_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'graduated', 'transferred'])],
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->where('school_id', $schoolId)],
            'scout_level_id' => ['required', 'integer', Rule::exists('scout_levels', 'id')],
        ];
    }
}
