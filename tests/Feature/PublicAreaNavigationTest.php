<?php

use App\Livewire\Auth\Register;
use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Journal;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolContext;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('public and dashboard pages expose the signed in profile and admin navigation', function (string $page) {
    $school = School::factory()->create();
    $user = User::factory()->create(['system_role' => 'super_admin']);

    $this->actingAs($user)->withSession(['active_school_id' => $school->id])
        ->get(route($page, $page === 'schools.landing' ? [$school] : []))
        ->assertOk()->assertSee($user->name)->assertSee('Global Area')->assertSee('School Area')
        ->assertSee('Admin Area')->assertSee(route('profile.edit'))->assertSee(route('schools.landing', $school));
})->with(['home', 'schools.landing', 'dashboard']);

test('students see Student Area on public pages', function (string $page) {
    $school = School::factory()->create();
    $user = User::factory()->create();
    Student::factory()->create(['school_id' => $school->id, 'user_id' => $user->id]);
    $user->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true]);

    $this->actingAs($user)->get(route($page, $page === 'schools.landing' ? [$school] : []))
        ->assertSee('Student Area')->assertDontSee('Admin Area')->assertSee(route('schools.landing', $school));
})->with(['home', 'schools.landing', 'dashboard']);

test('school admins see Admin Area even without public school middleware', function () {
    $school = School::factory()->create();
    $user = User::factory()->create();
    $user->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true]);
    setPermissionsTeamId($school->id);
    $user->assignRole(Role::create(['name' => 'school_admin', 'guard_name' => 'web']));
    setPermissionsTeamId(null);

    $this->actingAs($user)->get(route('home'))->assertSee('Admin Area')->assertDontSee('Student Area');
});

test('guest entry pages render shared navigation and registration', function (string $page) {
    $this->get(route($page))->assertOk()->assertSee('Global Area')->assertSee('School Area')
        ->assertSee(route('register'))->assertDontSee('Profil saya');
})->with(['home', 'login', 'register']);

test('registration uses the active school supplied by a tenant link', function () {
    $school = School::factory()->create();

    Livewire::withQueryParams(['school' => $school->id])->test(Register::class)->assertSet('school_id', $school->id);
});

test('registration ignores an inactive school supplied in a link', function () {
    $school = School::factory()->inactive()->create();

    Livewire::withQueryParams(['school' => $school->id])->test(Register::class)->assertSet('school_id', null);
});

test('public activity cards link to full details and documentation', function () {
    $school = School::factory()->create();
    $activity = Activity::factory()->create(['school_id' => $school->id, 'is_public' => true, 'status' => 'published', 'description' => 'Deskripsi lengkap latihan']);

    $this->get(route('schools.landing', $school))->assertSee(route('schools.activities.show', [$school, $activity->id]))
        ->assertSee(route('schools.documentation.show', [$school, $activity->id]));
    $this->get(route('schools.activities.show', [$school, $activity->id]))->assertSee('Deskripsi lengkap latihan');
    $this->get(route('schools.documentation.show', [$school, $activity->id]))->assertSee('Dokumentasi kegiatan belum diterbitkan.');
});

test('activity details refuse private draft scheduled and cross school content', function (string $reason, string $routeName) {
    $school = School::factory()->create();
    $attributes = ['school_id' => $school->id, 'is_public' => true, 'status' => 'published'];
    $attributes = array_merge($attributes, match ($reason) {
        'private' => ['is_public' => false],
        'draft' => ['status' => 'draft'],
        'scheduled' => ['published_at' => now()->addDay()],
        'other school' => ['school_id' => School::factory()->create()->id],
        default => [],
    });
    $activity = Activity::factory()->create($attributes);
    if ($reason === 'inactive school') {
        $school->update(['is_active' => false]);
    }
    if ($reason === 'deleted') {
        $activity->delete();
    }

    $this->get(route($routeName, [$school, $activity->id]))->assertNotFound();
})->with(['private', 'draft', 'scheduled', 'other school', 'inactive school', 'deleted'])
    ->with(['schools.activities.show', 'schools.documentation.show']);

test('announcement details respect publication expiry and tenant boundaries', function (string $state) {
    $school = School::factory()->create();
    $announcement = new Announcement([
        'title' => 'Informasi perkemahan', 'body' => 'Isi lengkap pengumuman <script>alert(1)</script>',
        'created_by' => User::factory()->create()->id, 'status' => $state === 'draft' ? 'draft' : 'published',
        'is_public' => $state !== 'private',
        'published_at' => $state === 'scheduled' ? now()->addDay() : now()->subDay(),
        'expires_at' => $state === 'expired' ? now()->subMinute() : null,
    ]);
    $announcement->school_id = $state === 'other school' ? School::factory()->create()->id : $school->id;
    $announcement->save();

    $response = $this->get(route('schools.announcements.show', [$school, $announcement->id]));
    if ($state === 'public') {
        $response->assertSee('Isi lengkap pengumuman')->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('schools.landing', $school))->assertSee(route('schools.announcements.show', [$school, $announcement->id]));
    } else {
        $response->assertNotFound();
    }
})->with(['public', 'private', 'draft', 'scheduled', 'expired', 'other school']);

test('documentation only exposes published journal content and attachments', function (string $status) {
    $school = School::factory()->create();
    $activity = Activity::factory()->create(['school_id' => $school->id, 'is_public' => true, 'status' => 'completed']);
    $journal = new Journal(['activity_id' => $activity->id, 'created_by' => $activity->created_by,
        'status' => $status, 'activity_description' => 'Cerita latihan yang lengkap', 'notes' => 'Catatan internal rahasia']);
    $journal->school_id = $school->id;
    $journal->save();
    $attachment = $journal->attachments()->make(['original_name' => 'Foto latihan', 'path' => 'journals/latihan.jpg', 'mime_type' => 'image/jpeg', 'size_bytes' => 1200, 'uploaded_by' => $activity->created_by]);
    $attachment->school_id = $school->id;
    $attachment->save();

    $response = $this->get(route('schools.documentation.show', [$school, $activity->id]));
    $response->assertDontSee('Catatan internal rahasia');
    if ($status === 'published') {
        $response->assertSee('Cerita latihan yang lengkap')->assertSee('journals/latihan.jpg');
    } else {
        $response->assertDontSee('Cerita latihan yang lengkap')->assertDontSee('journals/latihan.jpg');
    }
})->with(['published', 'draft']);

test('visiting a public school does not inherit or change the dashboard tenant', function () {
    $activeSchool = School::factory()->create();
    $publicSchool = School::factory()->create();
    $activity = Activity::factory()->create(['school_id' => $publicSchool->id, 'is_public' => true, 'status' => 'published']);
    app(SchoolContext::class)->set($activeSchool);

    $this->withSession(['active_school_id' => $activeSchool->id])->get(route('schools.activities.show', [$publicSchool, $activity->id]))
        ->assertSee($activity->title)->assertSessionHas('active_school_id', $activeSchool->id);
});
