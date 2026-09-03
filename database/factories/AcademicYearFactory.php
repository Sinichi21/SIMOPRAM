<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = (int) fake()->numberBetween(2020, 2040);

        return [
            'school_id' => School::factory(),
            'name' => $startYear.'/'.($startYear + 1),
            'start_date' => $startYear.'-07-01',
            'end_date' => ($startYear + 1).'-06-30',
            'is_active' => true,
        ];
    }
}
