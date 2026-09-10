<?php

use App\Livewire\Assessments\GradeRanges;
use App\Livewire\Assessments\Scores;
use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\FinalGrade;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use App\Support\SchoolContext;
use Livewire\Livewire;

beforeEach(function () {
    app(SchoolContext::class)->set(School::factory()->create());
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
});

test('grade ranges provide suggested descriptions while manual student descriptions survive recalculation', function () {
    Livewire::test(GradeRanges::class)->call('save')->assertHasNoErrors();
    $year = AcademicYear::factory()->create(['school_id' => app(SchoolContext::class)->id()]);
    $config = AssessmentConfig::query()->create(['academic_year_id' => $year->id, 'name' => 'Penilaian', 'is_active' => true]);
    $student = Student::factory()->create(['school_id' => app(SchoolContext::class)->id()]);
    $service = app(AssessmentService::class);
    $grade = $service->calculateFinalGrade($config, $student);
    expect($grade->description)->toBe('Perlu bimbingan');
    Livewire::test(Scores::class)->call('editDescription', $student->id)
        ->assertSet('suggestedDescription', 'Perlu bimbingan')->set('descriptionText', 'Aktif membantu teman; perlu latihan kedisiplinan.')
        ->call('saveDescription')->assertHasNoErrors();
    Livewire::test(GradeRanges::class)->set('ranges.3.description', 'Perlu latihan rutin')->call('save')->assertHasNoErrors();
    $grade = $service->calculateFinalGrade($config, $student);
    expect($grade->description)->toBe('Aktif membantu teman; perlu latihan kedisiplinan.');
    expect($grade->getRawOriginal('description'))->toBe('Perlu latihan rutin');
    Livewire::test(Scores::class)->call('editDescription', $student->id)->call('saveDescription', true)->assertHasNoErrors();
    expect($grade->fresh()->description)->toBe('Perlu latihan rutin');
    expect($grade->fresh()->manual_description)->toBeNull();
});

test('ranges reject overlaps gaps and duplicate predicates', function (string $case) {
    $component = Livewire::test(GradeRanges::class);
    if ($case === 'overlap') {
        $component->set('ranges.1.max_score', 90);
    } elseif ($case === 'gap') {
        $component->set('ranges.1.max_score', 89);
    } else {
        $component->set('ranges.1.letter_grade', 'A');
    }
    $component->call('save')->assertHasErrors();
    $this->assertDatabaseCount('grade_scale_configs', 0);
})->with(['overlap', 'gap', 'duplicate']);

test('description editing cannot cross schools or bypass permissions', function () {
    $year = AcademicYear::factory()->create(['school_id' => app(SchoolContext::class)->id()]);
    $config = AssessmentConfig::query()->create(['academic_year_id' => $year->id, 'name' => 'Penilaian', 'is_active' => true]);
    $student = Student::factory()->create(['school_id' => app(SchoolContext::class)->id()]);
    $grade = FinalGrade::query()->create(['assessment_config_id' => $config->id, 'student_id' => $student->id, 'final_score' => 80, 'calculated_at' => now()]);
    app(SchoolContext::class)->set(School::factory()->create());
    Livewire::test(Scores::class)->set('configId', $config->id)->call('editDescription', $student->id)->assertNotFound();
    $this->actingAs(User::factory()->create(['system_role' => 'student']));
    Livewire::test(GradeRanges::class)->assertForbidden();
    Livewire::test(Scores::class)->call('editDescription', $student->id)->assertForbidden();
    expect($grade->fresh()->manual_description)->toBeNull();
});
