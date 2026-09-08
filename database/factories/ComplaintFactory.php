<?php

namespace Database\Factories;

use App\Models\Complaint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Complaint> */
class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference' => 'ADU-'.Str::upper(Str::random(24)),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'category' => 'Layanan aplikasi',
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'status' => 'new',
        ];
    }
}
