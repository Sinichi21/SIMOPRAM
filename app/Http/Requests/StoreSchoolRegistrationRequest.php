<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRegistrationRequest extends FormRequest
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
            'school_name' => ['required', 'string', 'max:200'],
            'npsn' => ['required', 'string', 'max:20', 'unique:schools,npsn', 'unique:school_registration_requests,npsn'],
            'level' => ['required', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:100'],
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'contact_email' => ['required', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
