<?php

namespace Database\Factories;

use App\Models\ActivityJudge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityJudge>
 */
class ActivityJudgeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => \App\Models\School::factory(),
            'activity_assessment_id' => fn (array $attributes) => \App\Models\ActivityAssessment::factory()->special()->published()->create(['school_id' => $attributes['school_id']]),
            'name' => fake()->name(),
            'token_hash' => hash('sha256', \Illuminate\Support\Str::random(64)),
            'starts_at' => now()->subHour(),
            'expires_at' => now()->addHour(),
        ];
    }
}
