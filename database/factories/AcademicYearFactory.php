<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /*
         * Gunakan rentang khusus factory/test yang tidak berbenturan
         * dengan tahun ajaran eksplisit yang digunakan pada feature test
         * seperti 2025/2026 dan 2026/2027.
         *
         * unique() juga mencegah factory menghasilkan tahun yang sama
         * berulang kali selama satu proses test.
         */
        $startYear = fake()
            ->unique()
            ->numberBetween(2100, 2299);

        return [
            'school_id' => School::factory(),

            'name' => $startYear.'/'.($startYear + 1),

            'start_date' => sprintf(
                '%d-07-01',
                $startYear
            ),

            'end_date' => sprintf(
                '%d-06-30',
                $startYear + 1
            ),

            'is_active' => true,
        ];
    }
}
