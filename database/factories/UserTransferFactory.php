<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\User;
use App\Models\UserTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserTransfer>
 */
class UserTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'from_school_id' => School::factory(),
            'to_school_id' => School::factory(),
            'requested_by' => User::factory(),
            'role' => 'student',
            'reason' => 'Pindah sekolah',
            'status' => 'pending',
        ];
    }
}
