<?php

namespace Database\Factories;

use App\Models\LandingPageSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandingPageSetting>
 */
class LandingPageSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'global',
            'content' => [],
        ];
    }
}
