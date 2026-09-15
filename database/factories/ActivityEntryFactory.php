<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityEntry;
use App\Services\ActivityEntryService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityEntry>
 */
class ActivityEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory()->publicRegistration(),
            'name' => fake()->name(), 'category' => 'individual', 'status' => 'active', 'validation_status' => 'pending',
            'answers' => [], 'form_snapshot' => [], 'terms_snapshot' => ActivityEntryService::DEFAULT_TERMS,
            'declaration_accepted_at' => now(), 'terms_accepted_at' => now(),
        ];
    }
}
