<?php

namespace Database\Seeders;

use App\Models\GradeScale;
use App\Models\GradeScaleConfig;
use App\Models\School;
use Illuminate\Database\Seeder;

class GradeScaleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::query()->get() as $school) {
            $config =
                GradeScaleConfig::query()
                    ->firstOrCreate(
                        [
                            'school_id' => $school->id,

                            'name' => 'Skala Nilai Default',
                        ],
                        [
                            'is_active' => true,
                        ]
                    );

            $scales = [
                [
                    'letter_grade' => 'A',
                    'min_score' => 86,
                    'max_score' => 100,
                    'description' => 'Sangat Baik',
                    'sort_order' => 1,
                ],
                [
                    'letter_grade' => 'B',
                    'min_score' => 76,
                    'max_score' => 85.99,
                    'description' => 'Baik',
                    'sort_order' => 2,
                ],
                [
                    'letter_grade' => 'C',
                    'min_score' => 66,
                    'max_score' => 75.99,
                    'description' => 'Cukup',
                    'sort_order' => 3,
                ],
                [
                    'letter_grade' => 'D',
                    'min_score' => 0,
                    'max_score' => 65.99,
                    'description' => 'Perlu Bimbingan',
                    'sort_order' => 4,
                ],
            ];

            foreach ($scales as $scale) {
                GradeScale::query()
                    ->updateOrCreate(
                        [
                            'school_id' => $school->id,

                            'grade_scale_config_id' => $config->id,

                            'letter_grade' => $scale[
                                    'letter_grade'
                                ],
                        ],
                        $scale
                    );
            }
        }
    }
}
