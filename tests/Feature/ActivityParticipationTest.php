<?php

use App\Livewire\Assessments\Activities\Participation;
use App\Livewire\Assessments\Scores;
use App\Models\Activity;
use App\Models\AssessmentConfig;
use App\Models\AssessmentFactor;
use App\Models\Classroom;
use App\Models\School;
use App\Models\Semester;
use App\Models\SemesterClosure;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\User;
use App\Services\ActivityParticipationService;
use App\Services\AssessmentService;
use App\Support\SchoolContext;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function participationContext(): array
{
    test()->actingAs(User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]));
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    session(['active_school_id' => $school->id]);
    $activity = Activity::factory()->create(['school_id' => $school->id, 'status' => 'published']);
    $semester = Semester::query()->create(['academic_year_id' => $activity->academic_year_id, 'name' => 'Ganjil', 'semester_number' => 1, 'is_active' => true, 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);
    $activity->update(['semester_id' => $semester->id]);
    $config = AssessmentConfig::query()->create(['academic_year_id' => $activity->academic_year_id, 'semester_id' => $semester->id, 'name' => 'Semester', 'is_active' => true]);
    $factor = AssessmentFactor::factory()->create(['school_id' => $school->id, 'name' => 'Keaktifan', 'source_type' => 'manual']);
    $config->items()->create(['assessment_factor_id' => $factor->id, 'weight' => 100, 'sort_order' => 1]);
    $student = Student::factory()->create(['school_id' => $school->id]);
    $classroom = Classroom::query()->create(['name' => '1A', 'grade' => 1, 'is_active' => true]);
    $student->enrollments()->create(['academic_year_id' => $activity->academic_year_id, 'classroom_id' => $classroom->id, 'status' => 'active']);

    return compact('school', 'activity', 'config', 'factor', 'student');
}

test('activity points accumulate across activities and produce a capped semester percentage', function () {
    extract(participationContext());
    $service = app(ActivityParticipationService::class);
    $service->save($activity, $config, $factor->id, 40, [$student->id => ['points' => 10]]);
    $second = Activity::factory()->create(['school_id' => $school->id, 'academic_year_id' => $activity->academic_year_id, 'semester_id' => $activity->semester_id]);

    $service->save($second, $config, $factor->id, 40, [$student->id => ['points' => 20]]);

    $this->assertDatabaseHas('student_scores', ['student_id' => $student->id, 'score' => 75, 'source' => 'participation']);
    $service->save($second, $config, $factor->id, 40, [$student->id => ['points' => 50]]);
    $this->assertDatabaseHas('student_scores', ['student_id' => $student->id, 'score' => 100]);
    $this->assertDatabaseCount('activity_participations', 2);
    $service->save($second, $config, $factor->id, 100, [$student->id => ['points' => 50]]);
    $this->assertDatabaseHas('student_scores', ['student_id' => $student->id, 'score' => 60]);
    app(AssessmentService::class)->syncAllScores($config->fresh());
    $this->assertDatabaseHas('final_grades', ['student_id' => $student->id, 'final_score' => 60]);
});

test('participation form saves points and exposes the semester recap', function () {
    extract(participationContext());

    Livewire::test(Participation::class, ['activityId' => $activity->id])
        ->set('factorId', $factor->id)->set('targetPoints', 50)
        ->set('entries.'.$student->id.'.points', 20)->call('save')->assertHasNoErrors()->assertSee('40.00');

    $this->assertDatabaseHas('student_scores', ['assessment_config_id' => $config->id, 'student_id' => $student->id, 'score' => 40]);
});

test('participation rejects invalid point totals without writing data', function ($points, $target) {
    extract(participationContext());

    expect(fn () => app(ActivityParticipationService::class)->save($activity, $config, $factor->id, $target, [$student->id => ['points' => $points]]))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('activity_participations', 0);
})->with([[-1, 100], [10, 0], [1000001, 100]]);

test('participation cannot assign points to a student outside the activity school', function () {
    extract(participationContext());
    $outsider = Student::factory()->create();

    expect(fn () => app(ActivityParticipationService::class)->save($activity, $config, $factor->id, 100, [$outsider->id => ['points' => 10]]))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('activity_participations', 0);
});

test('participation preserves explicit manual score overrides', function () {
    extract(participationContext());
    StudentScore::query()->create(['assessment_config_id' => $config->id, 'assessment_factor_id' => $factor->id, 'student_id' => $student->id, 'score' => 95, 'source' => 'manual']);

    app(ActivityParticipationService::class)->save($activity, $config, $factor->id, 100, [$student->id => ['points' => 10]]);

    $this->assertDatabaseHas('student_scores', ['student_id' => $student->id, 'score' => 95, 'source' => 'manual']);
});

test('locked semesters reject participation changes', function () {
    extract(participationContext());
    SemesterClosure::query()->create(['assessment_config_id' => $config->id, 'academic_year_id' => $config->academic_year_id, 'semester_id' => $config->semester_id, 'version' => 1, 'status' => 'locked', 'locked_at' => now(), 'locked_by' => auth()->id()]);

    expect(fn () => app(ActivityParticipationService::class)->save($activity, $config, $factor->id, 100, [$student->id => ['points' => 10]]))
        ->toThrow(ValidationException::class);
});

test('point configuration cannot change semester or factor once configured', function () {
    extract(participationContext());
    $service = app(ActivityParticipationService::class);
    $service->save($activity, $config, $factor->id, 100, [$student->id => ['points' => 10]]);
    $otherFactor = AssessmentFactor::factory()->create(['school_id' => $school->id, 'source_type' => 'manual']);
    $config->items()->create(['assessment_factor_id' => $otherFactor->id, 'weight' => 0, 'sort_order' => 2]);

    expect(fn () => $service->save($activity, $config, $otherFactor->id, 100, [$student->id => ['points' => 10]]))->toThrow(ValidationException::class);
    $otherActivity = Activity::factory()->create(['school_id' => $school->id]);
    expect(fn () => $service->save($otherActivity, $config, $factor->id, 100, [$student->id => ['points' => 10]]))->toThrow(ValidationException::class);
});

test('participation page rejects other schools and users without permission', function () {
    extract(participationContext());
    $otherActivity = Activity::factory()->create();
    $this->get(route('activity-participation.show', $otherActivity->id))->assertNotFound();
    $this->actingAs(User::factory()->create(['system_role' => 'student', 'is_active' => true]));
    auth()->user()->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true, 'joined_at' => now()]);
    session(['active_school_id' => $school->id]);
    $this->get(route('activity-participation.show', $activity->id))->assertForbidden();
});

test('saving semester scores preserves the automatic participation source', function () {
    extract(participationContext());
    app(ActivityParticipationService::class)->save($activity, $config, $factor->id, 100, [$student->id => ['points' => 20]]);

    Livewire::test(Scores::class)->set('scores.'.$student->id.'.'.$factor->id, 99)
        ->call('saveStudent', $student->id)->assertHasNoErrors();

    $this->assertDatabaseHas('student_scores', ['student_id' => $student->id, 'score' => 20, 'source' => 'participation']);
    expect(fn () => app(AssessmentService::class)->saveManualScore($config->fresh(), $student, $factor->id, 99))->toThrow(ValidationException::class);
});
