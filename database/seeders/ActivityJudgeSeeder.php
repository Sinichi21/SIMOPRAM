<?php

namespace Database\Seeders;

use App\Models\ActivityJudge;
use Illuminate\Database\Seeder;

class ActivityJudgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ActivityJudge::factory()->create();
    }
}
