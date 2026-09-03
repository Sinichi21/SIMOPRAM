<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'academic_year_id' => fn (array $attributes) => AcademicYear::factory()->create([
                'school_id' => $attributes['school_id'],
            ]),
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'activity_type' => 'regular',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHours(2),
            'status' => 'draft',
            'is_public' => false,
        ];
    }
}
