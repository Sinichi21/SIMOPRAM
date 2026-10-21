<?php

use App\\Livewire\\PeopleMerge\\Index as PeopleMergeIndex;
use App\\Models\\Person;
use App\\Models\\User;
use Livewire\\Livewire;

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
        ->toThrow(\\Symfony\\Component\\HttpKernel\\Exception\\HttpException::class);
});
