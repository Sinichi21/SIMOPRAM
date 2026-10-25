<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterApiRequest extends FormRequest
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
            'role' => ['required', 'in:student,coach,school_admin'],
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')->where('is_active', true)],
            'name' => ['required_unless:role,student', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'student_identifier' => ['required_if:role,student', 'nullable', 'string', 'max:30'],
            'nta' => ['nullable', 'string', 'max:100'],
            'coach_position' => ['required_if:role,coach', 'nullable', 'string', 'max:100'],
            'admin_position' => ['required_if:role,school_admin', 'nullable', 'string', 'max:100'],
        ];
    }
}
