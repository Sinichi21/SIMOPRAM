<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'phone' => ['nullable', 'string', 'max:30'],
            'requested_school_id' => [
                'required',
                'integer',
                Rule::exists('schools', 'id')->where('is_active', true),
            ],
            'requested_role' => [
                'required',
                Rule::in(['student', 'coach', 'school_admin']),
            ],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'phone' => filled($input['phone'] ?? null)
                ? trim($input['phone'])
                : null,
            'requested_school_id' => $input['requested_school_id'],
            'requested_role' => $input['requested_role'],
            'approval_status' => 'pending',
            'is_active' => false,
        ]);
    }
}
