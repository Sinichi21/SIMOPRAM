<?php

use App\Livewire\Admin\PublicContent;
use App\Livewire\Assessments\Activities\Edit;
use App\Livewire\Assessments\Activities\GlobalManage;
use App\Livewire\Assessments\Activities\Index;
use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\Announcement;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityJudgeService;
use App\Services\PublicAssessmentService;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

/** @return array{activity: Activity, assessment: ActivityAssessment} */
function publicCompetition(?School $school = null): array
{
    app(SchoolContext::class)->clear();
    $activity = Activity::factory()->create(['school_id' => $school?->id, 'academic_year_id' => null,
        'title' => 'Lomba Terbuka', 'activity_type' => 'competition', 'routine_session_no' => null,
        'status' => 'published', 'is_public' => true, 'start_at' => now()->subHour(), 'end_at' => now()->addHour()]);
    $assessment = ActivityAssessment::factory()->special()->published()->create(['school_id' => $school?->id, 'activity_id' => $activity->id]);
    if ($school) {
        app(SchoolContext::class)->set($school);
    }
    $assessment->criteria()->create(['name' => 'Teknik', 'max_score' => 50, 'weight' => 100]);
    foreach (['Garuda', 'Elang', 'Rajawali', 'Merpati'] as $name) {
        if ($school) {
            $student = Student::factory()->create(['school_id' => $school->id, 'name' => $name]);
            $assessment->targets()->create(['student_id' => $student->id]);
        } else {
            $assessment->targets()->create(['participant_name' => $name]);
        }
    }

    return compact('activity', 'assessment');
}

test('super admin manages global activities independently of the selected school', function () {
    $user = User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]);
    $school = School::factory()->create();
    $this->actingAs($user)->withSession(['active_school_id' => $school->id]);
    app(SchoolContext::class)->set($school);
    Livewire::test(PublicContent::class, ['kind' => 'activities'])->set('title', 'Lomba Nasional')
        ->set('body', 'Jadwal dan perlengkapan peserta.')->set('startsAt', now()->toDateTimeString())
        ->set('endsAt', now()->addDay()->toDateTimeString())->set('status', 'published')->call('save')->assertHasNoErrors();

    $this->assertDatabaseHas('activities', ['school_id' => null, 'academic_year_id' => null, 'title' => 'Lomba Nasional', 'is_public' => true]);
    $activity = Activity::withoutGlobalScope('school')->where('title', 'Lomba Nasional')->firstOrFail();
    $this->get(route('home'))->assertSee('Lomba Nasional');
    $this->get(route('public.activities.show', $activity->id))->assertSee('Jadwal dan perlengkapan peserta.')->assertSee('Penyelenggara');
    $this->get(route('schools.activities.show', [$school, $activity->id]))->assertNotFound();
    $this->get(route('admin.public-activities'))->assertOk();
    $this->get(route('admin.public-announcements'))->assertOk();
    $this->get(route('admin.public-assessments'))->assertOk();
});

test('super admin can publish edit and hide a global announcement', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $component = Livewire::test(PublicContent::class, ['kind' => 'announcements'])->set('title', 'Informasi Nasional')
        ->set('body', 'Isi pengumuman lengkap.')->set('expiresAt', now()->addDay()->toDateTimeString())
        ->set('status', 'published')->call('save')->assertHasNoErrors();
    $announcement = Announcement::withoutGlobalScope('school')->whereNull('school_id')->firstOrFail();
    $this->get(route('public.announcements.show', $announcement->id))->assertSee('Isi pengumuman lengkap.')->assertSee('Berlaku sampai');
    $component->call('edit', $announcement->id)->set('status', 'draft')->call('save')->assertHasNoErrors();

    $this->assertDatabaseHas('announcements', ['id' => $announcement->id, 'school_id' => null, 'status' => 'draft']);
    $this->get(route('public.announcements.show', $announcement->id))->assertNotFound();
});

test('global management rejects non super admins and guests', function () {
    $this->get(route('admin.public-activities'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['system_role' => 'user']));
    foreach (['admin.public-activities', 'admin.public-announcements', 'admin.public-assessments'] as $route) {
        $this->get(route($route))->assertForbidden();
    }
    Livewire::test(PublicContent::class)->assertForbidden();
    Livewire::test(GlobalManage::class)->assertForbidden();
});

test('global content cannot edit school records', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $activity = Activity::factory()->create();
    expect(fn () => Livewire::test(PublicContent::class)->call('edit', $activity->id))
        ->toThrow(ModelNotFoundException::class);
    Livewire::test(GlobalManage::class)->set('activityId', $activity->id)->set('title', 'Invalid')
        ->set('participants', 'Peserta')->set('criteria.0.name', 'Teknik')->call('save')->assertHasErrors('activityId');
    $this->assertDatabaseCount('activity_assessments', 0);
});

test('public global activity excludes private draft scheduled and cancelled records', function (array $attributes) {
    $activity = Activity::factory()->create(['school_id' => null, 'academic_year_id' => null, 'status' => 'published', 'is_public' => true, ...$attributes]);
    $this->get(route('public.activities.show', $activity->id))->assertNotFound();
    $this->get(route('home'))->assertDontSee($activity->title);
})->with([
    'private' => [['is_public' => false]], 'draft' => [['status' => 'draft']],
    'scheduled' => [['published_at' => now()->addDay()]], 'cancelled' => [['status' => 'cancelled']],
]);

test('global judging supports manual participants draft finalization and explicit public rankings', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    ['activity' => $activity] = publicCompetition();
    $component = Livewire::test(GlobalManage::class, ['activityId' => $activity->id])
        ->set('title', 'Pionering Umum')->set('participants', "Garuda\nElang\nRajawali\nMerpati")
        ->set('criteria.0.name', 'Teknik')->call('save')->assertHasNoErrors();
    $assessment = ActivityAssessment::withoutGlobalScope('school')->where('title', 'Pionering Umum')->firstOrFail();
    $component->call('activate', $assessment->id)->assertHasNoErrors()->assertSee('Buat Link Juri');
    ['judge' => $judge, 'token' => $token] = app(ActivityJudgeService::class)->invite($assessment->fresh(), 'Juri Umum');
    $criterion = $assessment->criteria()->first();
    $scores = [];
    foreach ($assessment->targets()->orderBy('id')->get() as $index => $target) {
        $scores[$target->id] = [$criterion->id => [95, 80, 70, 60][$index]];
    }
    auth()->logout();
    $this->get(route('activity-judges.show', $token))->assertSee('Garuda')->assertSee('Merpati');
    $this->post(route('activity-judges.store', $token), ['action' => 'save', 'scores' => $scores])->assertRedirect();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    Livewire::test(GlobalManage::class)->call('publishResults', $assessment->id, true)->assertHasErrors('publication');
    $this->post(route('activity-judges.store', $token), ['action' => 'finalize', 'scores' => $scores])->assertSee('Penilaian sudah final');
    $this->get(route('public.activities.results', [$activity->id, $assessment->id]))->assertNotFound();
    $component = Livewire::test(GlobalManage::class)->call('publishResults', $assessment->id, true)->assertHasNoErrors();
    $this->get(route('public.activities.show', $activity->id))->assertSee('Garuda')->assertSee('95.00')->assertDontSee('Merpati');
    $this->get(route('public.activities.results', [$activity->id, $assessment->id]))->assertSee('Merpati')->assertSee('60.00');
    $component->call('edit', $assessment->id)->set('participants', 'Pengganti')->call('save')->assertHasErrors('participants');
    expect($assessment->targets()->count())->toBe(4);
    $component->call('publishResults', $assessment->id, false)->assertHasNoErrors();
    $this->get(route('public.activities.results', [$activity->id, $assessment->id]))->assertNotFound();
    $this->get(route('activity-judges.show', $token))->assertGone();
    $this->assertDatabaseCount('student_scores', 0);
});

test('tenant results require publication and remain bound to the public school activity', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    ['activity' => $activity, 'assessment' => $assessment] = publicCompetition($school);
    ['judge' => $judge] = app(ActivityJudgeService::class)->invite($assessment, 'Juri Sekolah');
    $criterion = $assessment->criteria()->first();
    $scores = $assessment->targets()->get()->mapWithKeys(fn ($target) => [$target->id => [$criterion->id => 40]])->all();
    app(ActivityJudgeService::class)->save($judge, $scores, true);
    Livewire::test(Index::class)->call('publishResults', $assessment->id, true)->assertHasNoErrors();
    $this->get(route('schools.activities.show', [$school, $activity->id]))->assertSee('Garuda')->assertSee('80.00');
    $this->get(route('schools.activities.results', [$school, $activity->id, $assessment->id]))->assertSee('Merpati')
        ->assertViewHas('rankings', fn ($rankings) => $rankings->pluck('rank')->all() === [1, 1, 1, 1]);
    $this->get(route('schools.activities.results', [$otherSchool, $activity->id, $assessment->id]))->assertNotFound();
    $this->get(route('public.activities.results', [$activity->id, $assessment->id]))->assertNotFound();
    Livewire::test(Edit::class, ['assessmentId' => $assessment->id])
        ->call('publishResults', false)->assertHasNoErrors();
    $this->get(route('schools.activities.results', [$school, $activity->id, $assessment->id]))->assertNotFound();
    Livewire::test(Index::class)->call('publishResults', $assessment->id, true)->assertHasNoErrors();
    $activity->update(['is_public' => false]);
    $this->get(route('schools.activities.results', [$school, $activity->id, $assessment->id]))->assertNotFound();
});

test('regular activity public results exclude unassessed default zero scores', function () {
    ['assessment' => $assessment] = publicCompetition(School::factory()->create());
    $assessment->update(['is_special' => false]);
    $target = $assessment->targets()->first();
    $target->update(['normalized_score' => 0, 'assessed_at' => now()]);
    $rankings = app(PublicAssessmentService::class)->rankings($assessment);
    expect($rankings->first())->toMatchArray(['score' => 0.0, 'rank' => 1]);
    expect($rankings->whereNull('score'))->toHaveCount(3);
});

test('public results cannot be published from another school context', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    ['assessment' => $assessment] = publicCompetition(School::factory()->create());
    app(SchoolContext::class)->set(School::factory()->create());
    expect(fn () => Livewire::test(Index::class)->call('publishResults', $assessment->id, true))
        ->toThrow(ModelNotFoundException::class);
    expect($assessment->fresh()->results_published_at)->toBeNull();
});

test('expired and scheduled global announcements stay private', function (string $state) {
    $announcement = Announcement::create(['title' => 'Informasi tersembunyi', 'body' => 'Isi internal',
        'status' => 'published', 'is_public' => true, 'published_at' => $state === 'scheduled' ? now()->addDay() : now()->subDay(),
        'expires_at' => $state === 'expired' ? now()->subMinute() : null]);
    $this->get(route('home'))->assertDontSee('Informasi tersembunyi');
    $this->get(route('public.announcements.show', $announcement->id))->assertNotFound();
})->with(['expired', 'scheduled']);

test('global form rejects duplicate participants and invalid weights before activation', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    ['activity' => $activity] = publicCompetition();
    $component = Livewire::test(GlobalManage::class, ['activityId' => $activity->id])->set('title', 'Tes bobot')
        ->set('criteria.0.name', 'Teknik')->set('participants', "Garuda\nGaruda")->call('save')->assertHasErrors('names.0');
    $component->set('participants', 'Garuda')->set('criteria.0.weight', 50)->call('save')->assertHasNoErrors();
    $assessment = ActivityAssessment::where('title', 'Tes bobot')->firstOrFail();
    $component->call('activate', $assessment->id)->assertHasErrors('criteria');
    expect($assessment->fresh()->status)->toBe('draft');
});

test('public results publication requires permission even in a valid tenant', function () {
    ['assessment' => $assessment] = publicCompetition(School::factory()->create());
    $this->actingAs(User::factory()->create(['system_role' => 'user']));
    Livewire::test(Index::class)->call('publishResults', $assessment->id, true)->assertForbidden();
    expect($assessment->fresh()->results_published_at)->toBeNull();
});

test('public detail escapes user content and shows announcement publisher', function () {
    $school = School::factory()->create();
    $creator = User::factory()->create(['name' => 'Penerbit Sekolah']);
    $announcement = new Announcement(['created_by' => $creator->id, 'title' => '<script>alert(1)</script>',
        'body' => 'Isi lengkap', 'status' => 'published', 'is_public' => true]);
    $announcement->school_id = $school->id;
    $announcement->save();
    $this->get(route('schools.announcements.show', [$school, $announcement->id]))
        ->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)->assertSee('Penerbit Sekolah');
    $this->get(route('public.announcements.show', $announcement->id))->assertNotFound();
});
