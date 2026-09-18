<?php

namespace Database\Factories;

use App\Models\ActivityMessageDelivery;
use App\Models\ActivityRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityMessageDelivery>
 */
class ActivityMessageDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_registration_id' => ActivityRegistration::factory(),
            'title' => 'Pembaruan kegiatan',
            'body' => 'Lokasi kegiatan diperbarui.',
            'channel' => 'email',
            'status' => 'pending',
        ];
    }
}
