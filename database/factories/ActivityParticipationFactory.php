<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityParticipation;
use App\Models\AssessmentConfig;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityParticipation>
 */
class ActivityParticipationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'activity_id' => fn (array $attributes) => Activity::factory()->create(['school_id' => $attributes['school_id']]),
            'student_id' => fn (array $attributes) => Student::factory()->create(['school_id' => $attributes['school_id']]),
            'assessment_config_id' => function (array $attributes): int {
                $activity = Activity::withoutGlobalScope('school')->findOrFail($attributes['activity_id']);
                $config = new AssessmentConfig(['name' => 'Keaktifan', 'academic_year_id' => $activity->academic_year_id, 'semester_id' => $activity->semester_id, 'is_active' => true]);
                $config->school_id = $attributes['school_id'];
                $config->save();

                return $config->id;
            },
            'points' => fake()->numberBetween(0, 20),
        ];
    }
}
