<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ActivityParticipationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\ActivityParticipation::factory()->create();
    }
}
