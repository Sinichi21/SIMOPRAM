<?php

use App\Livewire\PeopleMerge\Index as PeopleMergeIndex;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

it('allows only super admins to open the profile merge page', function (): void {
    $superAdmin = User::factory()->create(['system_role' => 'super_admin']);

    $this->actingAs($superAdmin)
        ->get('/admin/people/merge')
        ->assertOk()
        ->assertSee('Penggabungan Identitas Profil');

    $regularUser = User::factory()->create(['system_role' => 'school_admin']);

    $this->actingAs($regularUser)
        ->get('/admin/people/merge')
        ->assertForbidden();
});

it('links multiple unlinked accounts to one new person without deleting accounts', function (): void {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $first = User::factory()->create(['name' => 'Pembina Contoh']);
    $second = User::factory()->create(['name' => 'Pembina Contoh']);

    $this->actingAs($admin);

    Livewire::test(PeopleMergeIndex::class)
        ->set('profileType', 'user')
        ->set('selectedProfileIds', [$first->id, $second->id])
        ->set('newPersonName', 'Pembina Contoh')
        ->set('confirmLink', true)
        ->call('linkProfiles')
        ->assertHasNoErrors()
        ->assertSee('2 profil berhasil ditautkan');

    $person = Person::query()->where('name', 'Pembina Contoh')->firstOrFail();

    expect($first->fresh()->person_id)->toBe($person->id)
        ->and($second->fresh()->person_id)->toBe($person->id)
        ->and(User::query()->count())->toBe(3)
        ->and($person->users()->count())->toBe(2);
});

it('requires at least two profiles when creating a new identity', function (): void {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $profile = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(PeopleMergeIndex::class)
        ->set('profileType', 'user')
        ->set('selectedProfileIds', [$profile->id])
        ->set('newPersonName', 'Nama Contoh')
        ->set('confirmLink', true)
        ->call('linkProfiles')
        ->assertHasErrors(['selectedProfileIds']);

    expect(Person::query()->count())->toBe(0)
        ->and($profile->fresh()->person_id)->toBeNull();
});

it('can link one unlinked account to an existing person', function (): void {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $profile = User::factory()->create();
    $person = Person::query()->create(['name' => 'Identitas Ada']);

    $this->actingAs($admin);

    Livewire::test(PeopleMergeIndex::class)
        ->set('profileType', 'user')
        ->set('selectedProfileIds', [$profile->id])
        ->set('targetPersonId', $person->id)
        ->set('confirmLink', true)
        ->call('linkProfiles')
        ->assertHasNoErrors();

    expect($profile->fresh()->person_id)->toBe($person->id)
        ->and($person->fresh()->users()->count())->toBe(1);
});

it('does not allow a non-super-admin to invoke profile linking', function (): void {
    $regularUser = User::factory()->create(['system_role' => 'school_admin']);
    $first = User::factory()->create();
    $second = User::factory()->create();

    $this->actingAs($regularUser);

    expect(fn () => Livewire::test(PeopleMergeIndex::class)
        ->set('profileType', 'user')
        ->set('selectedProfileIds', [$first->id, $second->id])
        ->set('newPersonName', 'Nama Contoh')
        ->set('confirmLink', true)
        ->call('linkProfiles'))
        ->toThrow(\InvalidArgumentException::class);

    expect(Person::query()->count())->toBe(0)
        ->and($first->fresh()->person_id)->toBeNull()
        ->and($second->fresh()->person_id)->toBeNull();
});

it('links multiple coach profiles across schools without deleting or changing their school ownership', function (): void {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $schoolOne = \App\Models\School::factory()->create();
    $schoolTwo = \App\Models\School::factory()->create();

    $coachOne = $schoolOne->coaches()->create([
        'name' => 'Pembina Contoh',
        'is_active' => true,
    ]);
    $coachTwo = $schoolTwo->coaches()->create([
        'name' => 'Pembina Contoh',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(PeopleMergeIndex::class)
        ->set('profileType', 'coach')
        ->set('selectedProfileIds', [$coachOne->id, $coachTwo->id])
        ->set('newPersonName', 'Pembina Contoh')
        ->set('confirmLink', true)
        ->call('linkProfiles')
        ->assertHasNoErrors();

    $coachOne->refresh();
    $coachTwo->refresh();

    expect($coachOne->person_id)->not->toBeNull()
        ->and($coachTwo->person_id)->toBe($coachOne->person_id)
        ->and($coachOne->school_id)->toBe($schoolOne->id)
        ->and($coachTwo->school_id)->toBe($schoolTwo->id)
        ->and(\App\Models\Coach::query()->count())->toBe(2)
        ->and(Person::query()->count())->toBe(1);
});
