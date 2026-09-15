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
    public function publicRegistration(): static
    {
        return $this->state(fn (): array => ['school_id' => null, 'academic_year_id' => null,
            'activity_type' => 'competition', 'routine_session_no' => null, 'status' => 'published', 'is_public' => true,
            'approval_status' => 'approved', 'registration_open' => true, 'registration_categories' => ['individual'],
            'start_at' => now()->subHour(), 'end_at' => now()->addDay()]);
    }

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
            'routine_session_no' => 1,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHours(2),
            'status' => 'draft',
            'is_public' => false,
        ];
    }
}
