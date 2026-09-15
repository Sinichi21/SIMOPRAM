<?php

namespace Database\Factories;

use App\Models\ActivityEntry;
use App\Models\ActivityRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityRegistration>
 */
class ActivityRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_id' => ActivityEntry::factory(),
            'activity_id' => fn (array $attributes): int => ActivityEntry::findOrFail($attributes['entry_id'])->activity_id,
            'name' => fake()->name(), 'school_name' => fake()->company(), 'identifier' => fake()->numerify('NTA#######'),
            'role' => 'student', 'is_reserve' => false, 'channel' => 'email', 'destination' => fake()->safeEmail(),
            'identity_key' => hash('sha256', fake()->uuid()), 'status' => 'active', 'access_version' => 1,
            'delivery_status' => 'pending', 'link_requested_at' => now()->subMinutes(2),
        ];
    }
}
