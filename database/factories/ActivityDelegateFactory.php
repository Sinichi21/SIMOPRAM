<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityDelegate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityDelegate>
 */
class ActivityDelegateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory()->publicRegistration(),
            'user_id' => User::factory(), 'requested_by' => User::factory()->state(['system_role' => 'super_admin']),
            'status' => 'approved', 'reviewed_at' => now(),
        ];
    }
}
