<?php

use App\Livewire\Assessments\Activities\Index;
use App\Livewire\Assessments\Activities\Judges;
use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\AssessmentConfig;
use App\Models\AssessmentFactor;
use App\Models\MessagingSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityAssessmentService;
use App\Services\ActivityJudgeService;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function judgeContext(): array
{
    test()->actingAs(User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]));
    $school = School::factory()->create(['is_active' => true]);
    app(SchoolContext::class)->set($school);
    session(['active_school_id' => $school->id]);
    $activity = Activity::factory()->create(['school_id' => $school->id, 'start_at' => now()->subHour(), 'end_at' => now()->addHour(), 'status' => 'published']);
    $assessment = ActivityAssessment::factory()->special()->published()->create(['school_id' => $school->id, 'activity_id' => $activity->id]);
    $criterion = $assessment->criteria()->create(['name' => 'Teknik', 'max_score' => 50, 'weight' => 100, 'sort_order' => 1]);
    $student = Student::factory()->create(['school_id' => $school->id]);
    $target = $assessment->targets()->create(['student_id' => $student->id]);

    return compact('school', 'activity', 'assessment', 'criterion', 'target');
}

test('judge invitations can be sent by whatsapp and revoked invitations cannot be sent again', function () {
    extract(judgeContext());
    MessagingSetting::factory()->create(['enabled' => true, 'options' => ['token' => 'secret']]);
    Http::preventStrayRequests();
    Http::fake(['https://api.fonnte.com/send' => Http::response(['status' => true])]);
    $component = Livewire::test(Judges::class, ['assessmentId' => $assessment->id])
        ->set('judgeName', 'Juri Undangan')->call('invite')->assertHasNoErrors()
        ->set('messageDestination', '081234567890')->call('sendInvitation')->assertHasNoErrors();
    $url = $component->get('invitationUrl');
    Http::assertSent(fn ($request) => $request['target'] === '6281234567890' && str_contains($request['message'], $url));
    $assessment->judges()->first()->update(['revoked_at' => now()]);
    $component->call('sendInvitation')->assertStatus(410);
    Http::assertSentCount(1);
});

test('special assessments can be created without a semester factor', function () {
    extract(judgeContext());

    Livewire::test(Index::class)->set('activityId', $activity->id)->set('isSpecial', true)
        ->set('title', 'Lomba Kemah')->call('create')->assertHasNoErrors();

    $this->assertDatabaseHas('activity_assessments', ['title' => 'Lomba Kemah', 'is_special' => true, 'assessment_factor_id' => null]);
});

test('external judge can save a draft and finalize only once without logging in', function () {
    extract(judgeContext());
    ['judge' => $judge, 'token' => $token] = app(ActivityJudgeService::class)->invite($assessment, 'Juri Tamu');
    auth()->logout();
    $url = route('activity-judges.show', $token);
    $scores = [$target->id => [$criterion->id => 40]];

    $this->get($url)->assertOk()->assertSee('Juri Tamu')->assertHeader('Referrer-Policy', 'no-referrer');
    $this->post($url, ['scores' => $scores, 'action' => 'save'])->assertRedirect($url);
    expect($judge->fresh()->finalized_at)->toBeNull();
    $this->post($url, ['scores' => $scores, 'action' => 'finalize'])->assertOk()->assertSee('Penilaian sudah final');
    $this->get($url)->assertGone();
    $this->post($url, ['scores' => [$target->id => [$criterion->id => 1]], 'action' => 'save'])->assertGone();

    expect((float) $judge->fresh()->scores[$target->id][$criterion->id])->toBe(40.0);
    $this->assertDatabaseCount('student_scores', 0);
});

test('judge validation identifies the participant criterion and allowed maximum', function () {
    app()->setLocale('id');
    extract(judgeContext());
    ['judge' => $judge, 'token' => $token] = app(ActivityJudgeService::class)->invite($assessment, 'Juri');
    $participant = $target->student->name;
    auth()->logout();
    $this->post(route('activity-judges.show', $token), [
        'scores' => [$target->id => [$criterion->id => 51]], 'action' => 'finalize',
    ])->assertSessionHasErrors(['scores.'.$target->id.'.'.$criterion->id => 'Nilai Teknik untuk '.$participant.' tidak boleh lebih dari '.$criterion->max_score.'.']);
    expect($judge->fresh()->finalized_at)->toBeNull();
});

test('judge links enforce the activity time window and revocation', function (string $state) {
    extract(judgeContext());
    ['judge' => $judge, 'token' => $token] = app(ActivityJudgeService::class)->invite($assessment, 'Juri');
    match ($state) {
        'future' => $judge->update(['starts_at' => now()->addMinute()]),
        'expired' => $judge->update(['expires_at' => now()]),
        'revoked' => app(ActivityJudgeService::class)->revoke($assessment, $judge->id),
        'cancelled' => $activity->update(['status' => 'cancelled']),
    };
    auth()->logout();

    $this->get(route('activity-judges.show', $token))->assertGone();
    $this->post(route('activity-judges.store', $token), ['scores' => [$target->id => [$criterion->id => 40]], 'action' => 'finalize'])->assertGone();
    expect($judge->fresh()->finalized_at)->toBeNull();
})->with(['future', 'expired', 'revoked', 'cancelled']);

test('judge cannot submit an unknown participant or out of range score', function (string $invalid) {
    extract(judgeContext());
    ['judge' => $judge, 'token' => $token] = app(ActivityJudgeService::class)->invite($assessment, 'Juri');
    $scores = match ($invalid) {
        'participant' => [999999 => [$criterion->id => 40]],
        'criterion' => [$target->id => [999999 => 40]],
        'range' => [$target->id => [$criterion->id => 51]],
        'missing' => [$target->id => [$criterion->id => null]],
    };
    auth()->logout();

    $this->postJson(route('activity-judges.store', $token), ['scores' => $scores, 'action' => 'finalize'])->assertUnprocessable();

    expect($judge->fresh()->finalized_at)->toBeNull();
})->with(['participant', 'criterion', 'range', 'missing']);

test('rankings average finalized judges and preserve tied ranks without semester scores', function () {
    extract(judgeContext());
    $other = Student::factory()->create(['school_id' => $school->id]);
    $second = $assessment->targets()->create(['student_id' => $other->id]);
    $service = app(ActivityJudgeService::class);
    $firstJudge = $service->invite($assessment, 'Juri Satu')['judge'];
    $secondJudge = $service->invite($assessment, 'Juri Dua')['judge'];
    $service->save($firstJudge, [$target->id => [$criterion->id => 40], $second->id => [$criterion->id => 30]], true);
    $service->save($secondJudge, [$target->id => [$criterion->id => 30], $second->id => [$criterion->id => 40]], false);
    expect($service->rankings($assessment)->first()['score'])->toBe(80.0);

    $service->save($secondJudge, [$target->id => [$criterion->id => 30], $second->id => [$criterion->id => 40]], true);

    $rows = $service->rankings($assessment->fresh());
    expect($rows->pluck('score')->all())->toBe([70.0, 70.0]);
    expect($rows->pluck('rank')->all())->toBe([1, 1]);
    expect(app(ActivityAssessmentService::class)->syncToStudentScores($assessment))->toBe(0);
    $this->assertDatabaseCount('student_scores', 0);
    Livewire::test(Judges::class, ['assessmentId' => $assessment->id])->assertSee('Hasil Final')->assertSee('70.00');
});

test('criteria and targets cannot be reopened after invitations are issued', function () {
    extract(judgeContext());
    app(ActivityJudgeService::class)->invite($assessment, 'Juri');

    expect(fn () => app(ActivityAssessmentService::class)->reopen($assessment))->toThrow(ValidationException::class);
    expect(fn () => app(ActivityAssessmentService::class)->prepareTargets($assessment))->toThrow(ValidationException::class);
});

test('invalid tokens never expose a judging form', function () {
    $this->get(route('activity-judges.show', str_repeat('x', 64)))->assertNotFound();
});

test('finalized judging cannot be revoked and prevents adding another judge', function () {
    extract(judgeContext());
    $service = app(ActivityJudgeService::class);
    $judge = $service->invite($assessment, 'Juri')['judge'];
    $service->save($judge, [$target->id => [$criterion->id => 40]], true);

    expect(fn () => $service->revoke($assessment, $judge->id))->toThrow(ValidationException::class);
    expect(fn () => $service->invite($assessment, 'Juri tambahan'))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('activity_judges', 1);
});

test('regular semester assessments cannot issue external judging links', function () {
    extract(judgeContext());
    $assessment->update(['is_special' => false]);

    expect(fn () => app(ActivityJudgeService::class)->invite($assessment, 'Juri'))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('activity_judges', 0);
});

test('judging panel requires permission and cannot open an assessment from another school', function () {
    extract(judgeContext());
    $this->actingAs(User::factory()->create(['system_role' => 'student', 'is_active' => true]));
    Livewire::test(Judges::class, ['assessmentId' => $assessment->id])->assertForbidden();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]));
    $otherSchool = School::factory()->create();
    app(SchoolContext::class)->set($otherSchool);
    session(['active_school_id' => $otherSchool->id]);

    expect(fn () => Livewire::test(Judges::class, ['assessmentId' => $assessment->id]))
        ->toThrow(ModelNotFoundException::class);
});

test('guest judging link uses its own school even when a different school is selected', function () {
    extract(judgeContext());
    ['token' => $token] = app(ActivityJudgeService::class)->invite($assessment, 'Juri');
    $otherSchool = School::factory()->create();
    app(SchoolContext::class)->set($otherSchool);
    session(['active_school_id' => $otherSchool->id]);

    $this->get(route('activity-judges.show', $token))->assertOk()->assertSee($assessment->title);

    expect(app(SchoolContext::class)->id())->toBe($otherSchool->id);
});

test('special assessment management page renders without a semester factor', function () {
    extract(judgeContext());

    $this->get(route('activity-assessments.edit', $assessment->id))
        ->assertOk()->assertSee('Juri &amp; Peringkat Kegiatan Khusus', false)->assertSee('Buat Link Juri');
});

test('regular activity rekap excludes special assessments even when they share a factor', function () {
    extract(judgeContext());
    $factor = AssessmentFactor::factory()->create(['school_id' => $school->id, 'source_type' => 'manual']);
    $config = AssessmentConfig::query()->create(['academic_year_id' => $activity->academic_year_id, 'name' => 'Semester', 'is_active' => true]);
    $config->items()->create(['assessment_factor_id' => $factor->id, 'weight' => 100, 'sort_order' => 1]);
    $assessment->update(['assessment_factor_id' => $factor->id]);
    $target->update(['normalized_score' => 100, 'assessed_at' => now()]);
    $regular = ActivityAssessment::factory()->published()->create(['school_id' => $school->id, 'activity_id' => $activity->id, 'assessment_factor_id' => $factor->id]);
    $regular->targets()->create(['student_id' => $target->student_id, 'normalized_score' => 40, 'assessed_at' => now()]);

    app(ActivityAssessmentService::class)->syncToStudentScores($regular);

    $this->assertDatabaseHas('student_scores', ['assessment_config_id' => $config->id, 'student_id' => $target->student_id, 'score' => 40]);
});
