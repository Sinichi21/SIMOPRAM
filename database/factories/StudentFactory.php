<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => null,
            'nis' => fake()->unique()->numerify('########'),
            'nisn' => fake()->unique()->numerify('##########'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['L', 'P']),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-13 years', '-6 years')->format('Y-m-d'),
            'phone' => null,
            'parent_phone' => null,
            'address' => fake()->address(),
            'photo' => null,
            'joined_at' => fake()->dateTimeBetween('-6 years', 'now')->format('Y-m-d'),
            'status' => 'active',
        ];
    }
}
