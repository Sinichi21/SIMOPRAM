<?php

namespace Database\Factories;

use App\Models\AssessmentFactor;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssessmentFactor>
 */
class AssessmentFactorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'school_id' => School::factory(),
            'name' => Str::title($name),
            'code' => Str::upper(Str::slug($name, '_')),
            'source_type' => 'manual',
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
