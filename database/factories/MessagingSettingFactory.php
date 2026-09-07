<?php

namespace Database\Factories;

use App\Models\MessagingSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessagingSetting>
 */
class MessagingSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel' => 'whatsapp',
            'enabled' => false,
            'options' => [],
        ];
    }
}
