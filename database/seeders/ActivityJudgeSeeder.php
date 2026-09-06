<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ActivityJudgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\ActivityJudge::factory()->create();
    }
}
