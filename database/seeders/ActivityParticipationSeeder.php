<?php

namespace Database\Seeders;

use App\Models\ActivityParticipation;
use Illuminate\Database\Seeder;

class ActivityParticipationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ActivityParticipation::factory()->create();
    }
}
