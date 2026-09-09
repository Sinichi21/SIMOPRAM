<?php

use App\Livewire\Settings\LandingContent;
use App\Models\Coach;
use App\Models\LandingPageSetting;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('super admin can edit global content and see escaped changes publicly', function () {
    $user = User::factory()->create(['system_role' => 'super_admin']);

    Livewire::actingAs($user)->test(LandingContent::class)
        ->set('content.hero_title', 'Pramuka <script>alert(1)</script>')
        ->set('content.contact_email', 'info@example.com')->call('save')->assertHasNoErrors();

    expect(LandingPageSetting::where('key', 'global')->first()->content['contact_email'])->toBe('info@example.com');
    $this->get(route('home'))->assertSee('Pramuka <script>alert(1)</script>')
        ->assertDontSee('<script>alert(1)</script>', false)->assertSee('mailto:info@example.com');
});

test('global editor route and menu are available to super admin', function () {
    $user = User::factory()->create(['system_role' => 'super_admin']);

    $this->actingAs($user)->get(route('settings.landing-content'))->assertOk()->assertSee('Simpan konten');
    $this->get(route('home'))->assertSee('Edit landing page');
});

test('global editor rejects non super admin accounts', function (string $role) {
    $user = User::factory()->create(['system_role' => $role]);

    $this->actingAs($user)->get(route('settings.landing-content'))->assertForbidden();
    $this->get(route('home'))->assertDontSee('Edit landing page');
})->with(['user', 'scout_admin']);

test('editor requires authentication', function () {
    $this->get(route('settings.landing-content'))->assertRedirect(route('login'));
});

test('school admin can edit only the owned tenant and sees its edit menu', function () {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = User::factory()->create();
    $user->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true]);
    setPermissionsTeamId($school->id);
    $user->assignRole(Role::create(['name' => 'school_admin', 'guard_name' => 'web']));

    Livewire::actingAs($user)->test(LandingContent::class, ['school' => $school])
        ->set('content.tagline', 'Sekolah kami berkarya')->set('content.email', 'sekolah@example.com')
        ->call('save')->assertHasNoErrors();

    expect($school->fresh()->tagline)->toBe('Sekolah kami berkarya');
    $this->get(route('schools.landing', $school))->assertSee('Sekolah kami berkarya')->assertSee('Edit halaman tenant');
    $this->get(route('settings.tenant-content', $school))->assertOk();
    $this->get(route('settings.tenant-content', $other))->assertForbidden();
    $this->get(route('schools.landing', $other))->assertDontSee('Edit halaman tenant');
});

test('tenant editor refuses members without a school admin role', function (string $role) {
    $school = School::factory()->create();
    $user = User::factory()->create();
    $user->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true]);
    setPermissionsTeamId($school->id);
    $user->assignRole(Role::create(['name' => $role, 'guard_name' => 'web']));

    $this->actingAs($user)->get(route('settings.tenant-content', $school))->assertForbidden();
})->with(['student', 'coach']);

test('tenant save rechecks a revoked membership', function () {
    $school = School::factory()->create();
    $user = User::factory()->create();
    $membership = $user->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true]);
    setPermissionsTeamId($school->id);
    $user->assignRole(Role::create(['name' => 'school_admin', 'guard_name' => 'web']));
    $component = Livewire::actingAs($user)->test(LandingContent::class, ['school' => $school])
        ->set('content.tagline', 'Tidak boleh tersimpan');
    $membership->update(['is_active' => false]);

    $component->call('save')->assertForbidden();
    expect($school->fresh()->tagline)->not->toBe('Tidak boleh tersimpan');
});

test('global editor rejects invalid email and unexpected content fields', function (string $field, mixed $value) {
    $user = User::factory()->create(['system_role' => 'super_admin']);

    Livewire::actingAs($user)->test(LandingContent::class)->set($field, $value)
        ->call('save')->assertHasErrors();

    $this->assertDatabaseCount('landing_page_settings', 0);
})->with(['email' => ['content.contact_email', 'javascript:alert(1)'], 'extra key' => ['content.is_active', false]]);

test('tenant editor validates colors and protects school identity', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['system_role' => 'super_admin']);

    Livewire::actingAs($user)->test(LandingContent::class, ['school' => $school])
        ->set('content.primary_color', 'red; color: blue')->call('save')->assertHasErrors('content.primary_color');
    expect($school->fresh()->primary_color)->toBe('#166534');
});

test('uploaded hero image is stored and rendered and can be removed', function () {
    Storage::fake('public');
    $user = User::factory()->create(['system_role' => 'super_admin']);
    $editor = Livewire::actingAs($user)->test(LandingContent::class)
        ->set('heroUpload', UploadedFile::fake()->image('hero.jpg'))->call('save')->assertHasNoErrors();
    $path = LandingPageSetting::where('key', 'global')->value('hero_image');

    Storage::disk('public')->assertExists($path);
    $this->get(route('home'))->assertSee(Storage::disk('public')->url($path));
    $editor->set('removeHero', true)->call('save')->assertHasNoErrors();
    expect(LandingPageSetting::where('key', 'global')->value('hero_image'))->toBeNull();
});

test('tenant images persist without changing another school', function () {
    Storage::fake('public');
    $school = School::factory()->create();
    $other = School::factory()->create();
    $user = User::factory()->create(['system_role' => 'super_admin']);

    Livewire::actingAs($user)->test(LandingContent::class, ['school' => $school])
        ->set('logoUpload', UploadedFile::fake()->image('logo.png'))->call('save')->assertHasNoErrors();

    Storage::disk('public')->assertExists($school->fresh()->logo);
    expect($other->fresh()->logo)->toBe($other->logo);
});

test('editor refuses non image uploads', function () {
    Storage::fake('public');
    $user = User::factory()->create(['system_role' => 'super_admin']);

    Livewire::actingAs($user)->test(LandingContent::class)
        ->set('heroUpload', UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'))
        ->call('save')->assertHasErrors('heroUpload');

    $this->assertDatabaseCount('landing_page_settings', 0);
    expect(Storage::disk('public')->allFiles('landing'))->toBeEmpty();
});

test('contact coach uses the linked email then school email and handles missing contacts', function (string $source) {
    $school = School::factory()->create(['email' => $source === 'none' ? null : 'school@example.com']);
    $user = $source === 'coach' ? User::factory()->create(['email' => 'coach@example.com']) : null;
    $coach = new Coach(['name' => 'Pembina Sekolah', 'user_id' => $user?->id, 'is_active' => true, 'gender' => 'L']);
    $coach->school_id = $school->id;
    $coach->save();

    $response = $this->get(route('schools.landing', $school));
    if ($source === 'none') {
        $response->assertSee('Email kontak belum tersedia.')->assertDontSee('mailto:');
    } else {
        $response->assertSee('mailto:'.($source === 'coach' ? 'coach@example.com' : 'school@example.com'))
            ->assertSee('Hubungi pembina');
    }
})->with(['coach', 'school', 'none']);
