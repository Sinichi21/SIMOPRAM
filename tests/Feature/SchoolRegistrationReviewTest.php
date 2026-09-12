<?php

use App\Livewire\SchoolRegistrations\Index;
use App\Models\School;
use App\Models\SchoolRegistrationRequest;
use App\Models\User;
use Livewire\Livewire;

test('super admin can open school requests without selecting a school', function () {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $registration = SchoolRegistrationRequest::factory()->create();

    $this->actingAs($admin)->get(route('school-registrations.index'))
        ->assertOk()->assertSee('Permohonan Sekolah')->assertSee($registration->school_name);
});

test('guests cannot open school requests', function () {
    $this->get(route('school-registrations.index'))->assertRedirect(route('login'));
});

test('non super admins cannot access school requests', function (string $role) {
    $user = User::factory()->create(['system_role' => $role]);

    Livewire::actingAs($user)->test(Index::class)->assertForbidden();
})->with(['school_admin', 'scout_admin', 'coach', 'student', 'principal']);

test('approval creates an active school and records the reviewer once', function () {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $registration = SchoolRegistrationRequest::factory()->create(['school_name' => 'SD Harapan']);
    School::factory()->create(['slug' => 'sd-harapan'])->delete();

    $component = Livewire::actingAs($admin)->test(Index::class)
        ->call('show', $registration->id)->call('approve')->assertHasNoErrors()
        ->assertSet('showDetail', false);

    $registration->refresh();
    $this->assertDatabaseHas('schools', [
        'id' => $registration->school_id, 'npsn' => $registration->npsn,
        'name' => 'SD Harapan', 'level' => 'SD', 'city' => $registration->city,
        'slug' => 'sd-harapan-2', 'is_active' => true,
    ]);
    expect($registration->status)->toBe('approved')
        ->and($registration->reviewed_by)->toBe($admin->id)
        ->and($registration->reviewed_at)->not->toBeNull();

    $component->call('approve')->assertHasErrors('review');
    $this->assertDatabaseCount('schools', 2);
});

test('rejection requires a reason and records it without creating a school', function () {
    $registration = SchoolRegistrationRequest::factory()->create();
    $admin = User::factory()->create(['system_role' => 'super_admin']);

    Livewire::actingAs($admin)->test(Index::class)->call('show', $registration->id)
        ->set('rejectionReason', ' ')->call('reject')->assertHasErrors('rejectionReason')
        ->assertSee('Alasan penolakan wajib diisi.')
        ->set('rejectionReason', str_repeat('x', 1001))->call('reject')->assertHasErrors('rejectionReason')
        ->set('rejectionReason', 'NPSN perlu dikoreksi.')->call('reject')->assertHasNoErrors()
        ->call('approve')->assertHasErrors('review');

    $this->assertDatabaseHas('school_registration_requests', [
        'id' => $registration->id, 'status' => 'rejected', 'school_id' => null,
        'rejection_reason' => 'NPSN perlu dikoreksi.', 'reviewed_by' => $admin->id,
    ]);
    $this->assertDatabaseCount('schools', 0);
});

test('approval refuses an npsn taken after submission including deleted schools', function (bool $deleted) {
    $registration = SchoolRegistrationRequest::factory()->create();
    $school = School::factory()->create(['npsn' => $registration->npsn]);
    if ($deleted) {
        $school->delete();
    }

    Livewire::actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->test(Index::class)->call('show', $registration->id)->call('approve')->assertHasErrors('review');

    $this->assertDatabaseHas('school_registration_requests', [
        'id' => $registration->id, 'status' => 'pending', 'school_id' => null, 'reviewed_at' => null,
    ]);
    $this->assertDatabaseCount('schools', 1);
})->with([false, true]);

test('requests can be searched and filtered and details escape submitted notes', function () {
    $pending = SchoolRegistrationRequest::factory()->create(['school_name' => 'Sekolah Dicari', 'notes' => '<script>alert(1)</script>']);
    $rejected = SchoolRegistrationRequest::factory()->create(['status' => 'rejected', 'school_name' => 'Sekolah Ditolak']);

    Livewire::actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->test(Index::class)->assertSee($pending->school_name)->assertDontSee($rejected->school_name)
        ->set('status', 'rejected')->assertSee($rejected->school_name)->assertDontSee($pending->school_name)
        ->set('status', '')->set('search', $pending->npsn)->assertSee($pending->school_name)->assertDontSee($rejected->school_name)
        ->call('show', $pending->id)->assertSee($pending->contact_email)
        ->assertSee($pending->notes)->assertDontSee($pending->notes, false);
});

test('actions are denied when super admin privileges are revoked', function () {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $registration = SchoolRegistrationRequest::factory()->create();
    $component = Livewire::actingAs($admin)->test(Index::class)->call('show', $registration->id);
    $admin->update(['system_role' => 'student']);

    $component->call('approve')->assertForbidden();

    $this->assertDatabaseHas('school_registration_requests', ['id' => $registration->id, 'status' => 'pending']);
    $this->assertDatabaseCount('schools', 0);
});
