<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignment' => ['sometimes', 'in:mine,all,coach'],
            'coach_id' => ['required_if:assignment,coach', 'integer'],
            'search' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'completed', 'cancelled'])],
            'activity_type' => ['sometimes', Rule::in(['regular', 'training', 'ceremony', 'camp', 'competition', 'service', 'other'])],
            'academic_year_id' => ['sometimes', 'integer', 'min:1'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
