<?php

namespace App\Http\Requests\Api\V1;

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
        return [
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
        ];
    }
}
