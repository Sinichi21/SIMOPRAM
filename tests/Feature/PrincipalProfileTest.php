<?php

use App\Livewire\Settings\PrincipalProfile;
use App\Models\DocumentSignatoryProfile;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Services\DocumentSignatoryService;
use App\Support\SchoolContext;
use Livewire\Livewire;

function principalProfileMember(School $school): User
{
    $user = User::factory()->create(['system_role' => 'principal', 'is_active' => true]);
    SchoolUserMembership::query()->create([
        'school_id' => $school->id, 'user_id' => $user->id,
        'is_active' => true, 'joined_at' => now()->toDateString(),
    ]);
    app(SchoolContext::class)->set($school);

    return $user;
}

test('principal edits own contact and signing identity from the profile page', function () {
    $school = School::factory()->create();
    $user = principalProfileMember($school);
    $this->actingAs($user)->withSession(['active_school_id' => $school->id]);
    $this->get(route('profile.edit'))->assertSee('Data Diri Kepala Sekolah')->assertSee($school->name);

    Livewire::test(PrincipalProfile::class)->set('phone', '+628123456789')
        ->set('position', 'Kepala Sekolah Definitif')->set('identifierNumber', '001234567890123456')
        ->call('save')->assertHasNoErrors();

    expect($user->fresh()->phone)->toBe('+628123456789');
    expect(app(DocumentSignatoryService::class)->resolve($user->id, $school->id))->toMatchArray([
        'position' => 'Kepala Sekolah Definitif', 'identity' => 'NIP. 001234567890123456',
    ]);
    Livewire::test(PrincipalProfile::class)->assertSet('identifierNumber', '001234567890123456')
        ->assertSet('phone', '+628123456789');
});

test('principal profile changes preserve other users and schools and do not reactivate disabled signing profiles', function () {
    $school = School::factory()->create();
    $user = principalProfileMember($school);
    $otherSchool = School::factory()->create();
    $other = User::factory()->create();
    $profiles = [
        DocumentSignatoryProfile::query()->create(['school_id' => $school->id, 'user_id' => $user->id, 'position' => 'Lama', 'is_active' => false]),
        DocumentSignatoryProfile::query()->create(['school_id' => $otherSchool->id, 'user_id' => $user->id, 'position' => 'Sekolah lain']),
        DocumentSignatoryProfile::query()->create(['school_id' => $school->id, 'user_id' => $other->id, 'position' => 'Orang lain']),
    ];
    $this->actingAs($user);

    Livewire::test(PrincipalProfile::class)->set('position', 'Kepala Sekolah')->call('save')->assertHasNoErrors();

    $this->assertDatabaseHas('document_signatory_profiles', ['id' => $profiles[0]->id, 'position' => 'Kepala Sekolah', 'is_active' => false]);
    $this->assertDatabaseHas('document_signatory_profiles', ['id' => $profiles[1]->id, 'position' => 'Sekolah lain']);
    $this->assertDatabaseHas('document_signatory_profiles', ['id' => $profiles[2]->id, 'position' => 'Orang lain']);
});

test('invalid principal details are rejected atomically', function () {
    $school = School::factory()->create();
    $user = principalProfileMember($school);
    $this->actingAs($user);

    Livewire::test(PrincipalProfile::class)->set('phone', 'invalid-phone')->set('position', ' ')
        ->set('identifierType', 'INVALID')->set('identifierNumber', str_repeat('1', 101))
        ->call('save')->assertHasErrors(['phone', 'position', 'identifierType', 'identifierNumber']);

    $this->assertDatabaseCount('document_signatory_profiles', 0);
    expect($user->fresh()->phone)->toBe($user->phone);
});

test('ordinary accounts cannot access principal personal details', function () {
    $this->actingAs(User::factory()->create());
    $this->get(route('profile.edit'))->assertDontSee('Data Diri Kepala Sekolah');
    Livewire::test(PrincipalProfile::class)->assertForbidden();
});

test('principal cannot save after their school membership is revoked', function () {
    $school = School::factory()->create();
    $user = principalProfileMember($school);
    $this->actingAs($user);
    $component = Livewire::test(PrincipalProfile::class);
    $user->schoolMemberships()->update(['is_active' => false]);

    $component->call('save')->assertForbidden();
    $this->assertDatabaseCount('document_signatory_profiles', 0);
});

test('principal without a school can still open account settings but cannot save school data', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'principal', 'is_active' => true]));
    $this->get(route('profile.edit'))->assertSee('Akun belum memiliki sekolah aktif.');
    Livewire::test(PrincipalProfile::class)->call('save')->assertStatus(409);
    $this->assertDatabaseCount('document_signatory_profiles', 0);
});

test('principal can clear optional contact and identity fields', function () {
    $school = School::factory()->create();
    $user = principalProfileMember($school);
    $user->update(['phone' => '08123456789']);
    DocumentSignatoryProfile::query()->create([
        'school_id' => $school->id, 'user_id' => $user->id,
        'position' => 'Kepala Sekolah', 'identifier_type' => 'NTA', 'identifier_number' => '00123',
    ]);
    $this->actingAs($user);

    Livewire::test(PrincipalProfile::class)->assertSet('identifierType', 'NTA')
        ->set('phone', '')->set('identifierNumber', '')->call('save')->assertHasNoErrors();

    expect($user->fresh()->phone)->toBeNull();
    $this->assertDatabaseHas('document_signatory_profiles', [
        'school_id' => $school->id, 'user_id' => $user->id,
        'identifier_type' => null, 'identifier_number' => null,
    ]);
});

test('stale principal form cannot save into a newly selected school', function () {
    $school = School::factory()->create();
    $user = principalProfileMember($school);
    $this->actingAs($user);
    $component = Livewire::test(PrincipalProfile::class);
    app(SchoolContext::class)->set(School::factory()->create());

    $component->call('save')->assertStatus(409);
    $this->assertDatabaseCount('document_signatory_profiles', 0);
});
