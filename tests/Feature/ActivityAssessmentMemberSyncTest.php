<?php

use App\Livewire\Assessments\Activities\Score;
use App\Models\AcademicYear;
use App\Models\ActivityAssessment;
use App\Models\AssessmentConfig;
use App\Models\School;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\Semester;
use App\Models\SemesterClosure;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\User;
use App\Services\ActivityAssessmentService;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/** @return array{assessment: ActivityAssessment, unit: ScoutUnit, config: AssessmentConfig} */
function memberSyncAssessment(): array
{
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    session(['active_school_id' => $school->id]);
    $assessment = ActivityAssessment::factory()->team()->published()->create(['school_id' => $school->id]);
    $semester = Semester::query()->create([
        'academic_year_id' => $assessment->activity->academic_year_id,
        'name' => 'Ganjil', 'semester_number' => 1, 'is_active' => true,
        'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
    ]);
    $assessment->activity->update(['semester_id' => $semester->id]);
    $config = AssessmentConfig::query()->create([
        'academic_year_id' => $assessment->activity->academic_year_id,
        'semester_id' => $semester->id, 'name' => 'Nilai kegiatan', 'is_active' => true,
    ]);
    $config->items()->create(['assessment_factor_id' => $assessment->assessment_factor_id, 'weight' => 100, 'sort_order' => 1]);
    $level = ScoutLevel::query()->create(['code' => 'PENGGALANG', 'name' => 'Penggalang', 'sort_order' => 1]);
    $unit = ScoutUnit::query()->create([
        'academic_year_id' => $assessment->activity->academic_year_id,
        'scout_level_id' => $level->id, 'name' => 'Elang', 'unit_type' => 'regu', 'is_active' => true,
    ]);

    return compact('assessment', 'unit', 'config');
}

function memberSyncStudent(ScoutUnit $unit, string $nis, bool $active = true): Student
{
    $student = Student::query()->create(['nis' => $nis, 'name' => 'Siswa '.$nis, 'status' => 'active']);
    $unit->memberships()->create([
        'student_id' => $student->id, 'position' => 'member', 'joined_at' => now(),
        'left_at' => $active ? null : now(),
    ]);

    return $student;
}

test('scorer can add late members to a scored team without changing scores or duplicating recipients', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]));
    ['assessment' => $assessment, 'unit' => $unit, 'config' => $config] = memberSyncAssessment();
    $criterion = $assessment->criteria()->create(['name' => 'Praktik', 'weight' => 100, 'max_score' => 100, 'sort_order' => 1]);
    app(ActivityAssessmentService::class)->prepareTargets($assessment);
    $target = $assessment->targets()->firstOrFail();
    app(ActivityAssessmentService::class)->saveTargetScores($target, [$criterion->id => 80], 'Nilai kemarin');
    $original = $target->fresh()->only(['total_score', 'normalized_score', 'assessed_at', 'assessed_by', 'notes']);
    $student = memberSyncStudent($unit, 'LATE');
    $departed = memberSyncStudent($unit, 'LEFT', false);

    Livewire::test(Score::class, ['assessmentId' => $assessment->id])
        ->assertSee('Perbarui Anggota Penerima Nilai')
        ->call('syncMembers')->assertHasNoErrors()->assertSee($student->name)
        ->call('syncMembers')->assertHasNoErrors();

    expect($target->members()->pluck('student_id')->all())->toBe([$student->id]);
    expect($target->fresh()->only(array_keys($original)))->toEqual($original);
    $this->assertDatabaseHas('activity_assessment_scores', ['activity_assessment_target_id' => $target->id, 'score' => 80]);
    $this->assertDatabaseHas('student_scores', ['assessment_config_id' => $config->id, 'student_id' => $student->id, 'score' => 80, 'source' => 'activity_assessment']);
    $this->assertDatabaseMissing('student_scores', ['student_id' => $departed->id]);
    $this->assertDatabaseHas('assessment_audit_logs', ['action' => 'activity_assessment.members_synced', 'subject_id' => $target->id]);
});

test('adding members preserves historical recipients and manual score overrides', function () {
    ['assessment' => $assessment, 'unit' => $unit, 'config' => $config] = memberSyncAssessment();
    $target = $assessment->targets()->create(['scout_unit_id' => $unit->id, 'normalized_score' => 70, 'total_score' => 70, 'assessed_at' => now()]);
    $old = memberSyncStudent($unit, 'OLD', false);
    $target->members()->create(['student_id' => $old->id]);
    $new = memberSyncStudent($unit, 'NEW');
    StudentScore::query()->create(['assessment_config_id' => $config->id, 'assessment_factor_id' => $assessment->assessment_factor_id, 'student_id' => $new->id, 'score' => 95, 'source' => 'manual']);

    expect(app(ActivityAssessmentService::class)->syncTeamMembers($target))->toBe(1);

    expect($target->members()->pluck('student_id')->all())->toBe([$old->id, $new->id]);
    $this->assertDatabaseHas('student_scores', ['student_id' => $old->id, 'score' => 70]);
    $this->assertDatabaseHas('student_scores', ['student_id' => $new->id, 'score' => 95, 'source' => 'manual']);
});

test('member synchronization rejects an empty team', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    ['assessment' => $assessment, 'unit' => $unit] = memberSyncAssessment();
    $target = $assessment->targets()->create(['scout_unit_id' => $unit->id]);

    Livewire::test(Score::class, ['assessmentId' => $assessment->id])
        ->call('syncMembers')->assertHasErrors('members');

    expect($target->members()->count())->toBe(0);
});

test('member synchronization refuses a locked semester', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    ['assessment' => $assessment, 'unit' => $unit, 'config' => $config] = memberSyncAssessment();
    $target = $assessment->targets()->create(['scout_unit_id' => $unit->id]);
    memberSyncStudent($unit, 'LOCKED');
    SemesterClosure::query()->create([
        'academic_year_id' => $config->academic_year_id, 'semester_id' => $config->semester_id,
        'assessment_config_id' => $config->id, 'status' => 'locked', 'locked_at' => now(),
    ]);

    Livewire::test(Score::class, ['assessmentId' => $assessment->id])
        ->call('syncMembers')->assertHasErrors('semester')->assertSee('Semester telah dikunci.');

    expect($target->members()->count())->toBe(0);
    $this->assertDatabaseCount('student_scores', 0);
});

test('member synchronization checks permission again on the action', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    ['assessment' => $assessment, 'unit' => $unit] = memberSyncAssessment();
    $target = $assessment->targets()->create(['scout_unit_id' => $unit->id]);
    memberSyncStudent($unit, 'FORBIDDEN');
    $component = Livewire::test(Score::class, ['assessmentId' => $assessment->id]);
    $this->actingAs(User::factory()->create(['system_role' => 'user']));

    $component->call('syncMembers')->assertForbidden();

    expect($target->members()->count())->toBe(0);
});

test('member synchronization cannot target a different assessment or school', function (bool $otherSchool) {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    ['assessment' => $assessment] = memberSyncAssessment();
    $component = Livewire::test(Score::class, ['assessmentId' => $assessment->id]);
    $other = ActivityAssessment::factory()->team()->published()->create([
        'school_id' => $otherSchool ? School::factory()->create()->id : $assessment->school_id,
    ]);
    $target = $other->targets()->create([]);

    expect(fn () => $component->update(
        updates: ['selectedTargetId' => $target->id],
        calls: [['method' => 'syncMembers', 'params' => [], 'path' => '']],
    ))->toThrow(ModelNotFoundException::class);

    expect($target->members()->count())->toBe(0);
})->with([false, true]);

test('member synchronization requires an active score configuration', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    ['assessment' => $assessment, 'unit' => $unit, 'config' => $config] = memberSyncAssessment();
    $target = $assessment->targets()->create(['scout_unit_id' => $unit->id]);
    memberSyncStudent($unit, 'NO-CONFIG');
    $config->update(['is_active' => false]);

    Livewire::test(Score::class, ['assessmentId' => $assessment->id])
        ->call('syncMembers')->assertHasErrors('members');

    expect($target->members()->count())->toBe(0);
});

test('member synchronization rejects forms outside published team mode', function (string $mode, string $status) {
    ['assessment' => $assessment, 'unit' => $unit] = memberSyncAssessment();
    $assessment->update(['mode' => $mode, 'status' => $status]);
    $target = $assessment->targets()->create(['scout_unit_id' => $unit->id]);
    memberSyncStudent($unit, 'INVALID-FORM');

    expect(fn () => app(ActivityAssessmentService::class)->syncTeamMembers($target))
        ->toThrow(ValidationException::class, 'Pembaruan anggota hanya tersedia');

    expect($target->members()->count())->toBe(0);
})->with([['individual', 'published'], ['team', 'draft']]);

test('member synchronization rejects a unit from another academic year or an inactive unit', function (string $reason) {
    ['assessment' => $assessment, 'unit' => $unit] = memberSyncAssessment();
    $target = $assessment->targets()->create(['scout_unit_id' => $unit->id]);
    memberSyncStudent($unit, 'INVALID-UNIT');
    $unit->update(match ($reason) {
        'inactive' => ['is_active' => false],
        'wrong year' => ['academic_year_id' => AcademicYear::factory()->create(['school_id' => $assessment->school_id])->id],
    });

    expect(fn () => app(ActivityAssessmentService::class)->syncTeamMembers($target))
        ->toThrow(ModelNotFoundException::class);

    expect($target->members()->count())->toBe(0);
})->with(['inactive', 'wrong year']);

test('member synchronization rolls back new recipients when score synchronization fails', function () {
    ['assessment' => $assessment, 'unit' => $unit] = memberSyncAssessment();
    $target = $assessment->targets()->create([
        'scout_unit_id' => $unit->id, 'assessed_at' => now(), 'normalized_score' => 101,
    ]);
    memberSyncStudent($unit, 'INVALID-SCORE');

    expect(fn () => app(ActivityAssessmentService::class)->syncTeamMembers($target))
        ->toThrow(ValidationException::class);

    expect($target->members()->count())->toBe(0);
    $this->assertDatabaseCount('student_scores', 0);
    $this->assertDatabaseCount('assessment_audit_logs', 0);
});
