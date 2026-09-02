<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        $name =
            'SD '.
            fake()->company();

        return [
            'npsn' => fake()->unique()->numerify(
                '########'
            ),

            'name' => $name,

            'slug' => Str::slug(
                $name
                .'-'
                .fake()->unique()->numberBetween(
                    1,
                    99999
                )
            ),

            'level' => 'SD',

            'address' => fake()->address(),

            'village' => fake()->city(),

            'district' => fake()->city(),

            'city' => 'Denpasar',

            'province' => 'Bali',

            'postal_code' => '80111',

            'latitude' => -8.65,

            'longitude' => 115.21,

            'phone' => fake()->numerify(
                '08##########'
            ),

            'email' => fake()->unique()->safeEmail(),

            'website' => null,

            'logo' => null,

            'timezone' => 'Asia/Makassar',

            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(
            fn () => [
                'is_active' => false,
            ]
        );
    }
}
