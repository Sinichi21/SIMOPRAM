<?php

namespace Database\Seeders;

use App\Models\ScoutLevel;
use Illuminate\Database\Seeder;

class ScoutLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'code' => 'SIAGA',
                'name' => 'Siaga',
                'minimum_age' => 7,
                'maximum_age' => 10,
                'sort_order' => 1,
            ],
            [
                'code' => 'PENGGALANG',
                'name' => 'Penggalang',
                'minimum_age' => 11,
                'maximum_age' => 15,
                'sort_order' => 2,
            ],
            [
                'code' => 'PENEGAK',
                'name' => 'Penegak',
                'minimum_age' => 16,
                'maximum_age' => 20,
                'sort_order' => 3,
            ],
            [
                'code' => 'PANDEGA',
                'name' => 'Pandega',
                'minimum_age' => 21,
                'maximum_age' => 25,
                'sort_order' => 4,
            ],
        ];

        foreach ($levels as $level) {
            ScoutLevel::updateOrCreate(
                ['code' => $level['code']],
                $level
            );
        }
    }
}
