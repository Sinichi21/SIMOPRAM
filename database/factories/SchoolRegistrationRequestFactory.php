<?php

namespace Database\Factories;

use App\Models\SchoolRegistrationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolRegistrationRequest>
 */
class SchoolRegistrationRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_name' => 'Sekolah '.fake()->company(),
            'npsn' => fake()->unique()->numerify('########'),
            'level' => 'SD',
            'city' => fake()->city(),
            'contact_name' => fake()->name(),
            'contact_phone' => '081234567890',
            'contact_email' => fake()->safeEmail(),
            'notes' => fake()->sentence(),
        ];
    }
}
