<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\AssessmentFactor;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityAssessment>
 */
class ActivityAssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | school_id sengaja ada di factory.
            |
            | Ini bukan mass-assignment dari aplikasi. Factory dipakai khusus
            | untuk fixture test.
            |--------------------------------------------------------------------------
            */

            'school_id' => School::factory(),

            'activity_id' => fn (array $attributes) => Activity::factory()->create([
                'school_id' => $attributes['school_id'],
            ]),

            'assessment_factor_id' => fn (array $attributes) => AssessmentFactor::factory()->create([
                'school_id' => $attributes['school_id'],
            ]),

            'title' => 'Penilaian '
                .fake()->words(
                    3,
                    true
                ),

            'mode' => 'individual',

            'status' => 'draft',

            'description' => fake()->sentence(),

            'created_by' => User::factory(),

            'published_by' => null,

            'published_at' => null,
        ];
    }

    public function individual(): static
    {
        return $this->state(
            fn (): array => [
                'mode' => 'individual',
            ]
        );
    }

    public function team(): static
    {
        return $this->state(
            fn (): array => [
                'mode' => 'team',
            ]
        );
    }

    public function published(): static
    {
        return $this->state(
            fn (): array => [
                'status' => 'published',

                'published_at' => now(),
            ]
        );
    }
}
