<?php

use App\Livewire\Activities\GlobalDelegates;
use App\Livewire\Admin\PublicContent;
use App\Models\Activity;
use App\Models\ActivityDelegate;
use App\Models\School;
use App\Models\User;
use App\Services\GlobalActivityAccess;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function activitySchoolAdmin(School $school): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true]);
    setPermissionsTeamId($school->id);
    $user->assignRole(Role::firstOrCreate(['name' => 'school_admin', 'guard_name' => 'web']));
    app(SchoolContext::class)->clear();

    return $user;
}

test('school requests stay private until super admin approves and ownership remains scoped', function () {
    $school = School::factory()->create();
    $owner = activitySchoolAdmin($school);
    $other = activitySchoolAdmin(School::factory()->create());
    $component = Livewire::actingAs($owner)->test(PublicContent::class)
        ->set('title', 'Kemah bersama lintas sekolah')->set('body', 'Agenda kemah umum')
        ->set('startsAt', now()->toDateTimeString())->set('endsAt', now()->addDay()->toDateTimeString())
        ->set('status', 'published')->set('registrationOpen', true)->call('save')->assertHasNoErrors();
    $activity = Activity::withoutGlobalScope('school')->where('title', 'Kemah bersama lintas sekolah')->firstOrFail();
    expect($activity->approval_status)->toBe('pending')->and($activity->school_id)->toBeNull()
        ->and($activity->organizer_school_id)->toBe($school->id)->and($activity->is_public)->toBeFalse();
    $this->get(route('public.activities.show', $activity))->assertNotFound();
    $this->get(route('admin.activity-participants', $activity))->assertForbidden();
    Livewire::actingAs($other)->test(PublicContent::class)->assertDontSee('Kemah bersama lintas sekolah');
    $super = User::factory()->create(['system_role' => 'super_admin']);
    Livewire::actingAs($super)->test(PublicContent::class)->call('reviewActivity', $activity->id, true)->assertHasNoErrors();
    $this->get(route('public.activities.show', $activity))->assertOk()->assertSee('Registrasi peserta');
    $this->actingAs($owner)->get(route('admin.activity-participants', $activity))->assertOk();
    $this->get(route('admin.public-announcements'))->assertForbidden();
    $this->actingAs($other)->get(route('admin.activity-participants', $activity))->assertForbidden();
    $owner->schoolMemberships()->update(['is_active' => false]);
    expect(app(GlobalActivityAccess::class)->canManage($owner, $activity))->toBeFalse();
});

test('rejection requires a reason and school admin can resubmit without publishing', function () {
    $owner = activitySchoolAdmin($school = School::factory()->create());
    $activity = Activity::factory()->publicRegistration()->create(['organizer_school_id' => $school->id, 'approval_status' => 'pending', 'is_public' => false, 'description' => 'Rencana kegiatan']);
    $super = User::factory()->create(['system_role' => 'super_admin']);
    Livewire::actingAs($super)->test(PublicContent::class)->call('reviewActivity', $activity->id, false)->assertHasErrors();
    Livewire::test(PublicContent::class)->set('rejectionReason', 'Perbaiki jadwal')->call('reviewActivity', $activity->id, false)->assertHasNoErrors();
    expect($activity->fresh()->approval_status)->toBe('rejected');
    Livewire::actingAs($owner)->test(PublicContent::class)->call('edit', $activity->id)->set('status', 'published')->call('save')->assertHasNoErrors();
    expect($activity->fresh()->approval_status)->toBe('pending')->and($activity->fresh()->is_public)->toBeFalse();
    Livewire::test(PublicContent::class)->call('reviewActivity', $activity->id, true)->assertForbidden();
});

test('school delegates are approved locally and require super admin approval across schools', function () {
    $owner = activitySchoolAdmin($school = School::factory()->create());
    $activity = Activity::factory()->publicRegistration()->create(['organizer_school_id' => $school->id]);
    $otherActivity = Activity::factory()->publicRegistration()->create();
    $local = User::factory()->create();
    $local->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true]);
    $outside = User::factory()->create();
    $this->actingAs($owner);
    $service = app(GlobalActivityAccess::class);
    $localGrant = $service->delegate($activity, $local->id);
    $outsideGrant = $service->delegate($activity, $outside->id);
    expect($localGrant->status)->toBe('approved')->and($outsideGrant->status)->toBe('pending')
        ->and($service->canManage($local, $activity))->toBeTrue()->and($service->canManage($outside, $activity))->toBeFalse()
        ->and($service->canManage($local, $otherActivity))->toBeFalse();
    Livewire::test(GlobalDelegates::class, ['activityId' => $activity->id])->assertSee('Menunggu persetujuan');
    Livewire::test(GlobalDelegates::class, ['activityId' => $activity->id])->call('review', $outsideGrant->id, true)->assertForbidden();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $service->reviewDelegate($outsideGrant->id, true);
    $this->actingAs($outside)->get(route('admin.activity-participants', $activity))->assertOk();
    $this->get(route('admin.activity-participants', $otherActivity))->assertForbidden();
    Livewire::test(GlobalDelegates::class, ['activityId' => $activity->id])->set('userId', $local->id)->call('assign')->assertForbidden();
    $this->actingAs($owner);
    $service->revoke($activity, $outsideGrant->id);
    expect($service->canManage($outside, $activity))->toBeFalse();
    $this->actingAs($outside)->get(route('admin.activity-participants', $activity))->assertForbidden();
});

test('super admin may delegate any active user but cannot leak another activity through child ids', function () {
    $super = User::factory()->create(['system_role' => 'super_admin']);
    $user = User::factory()->create();
    $activity = Activity::factory()->publicRegistration()->create();
    $foreign = ActivityDelegate::factory()->create();
    $this->actingAs($super);
    $grant = app(GlobalActivityAccess::class)->delegate($activity, $user->id);
    expect($grant->status)->toBe('approved');
    $this->actingAs($user)->get(route('admin.public-assessments', ['activity' => $activity->id]))->assertOk();
    $this->actingAs($super);
    expect(fn () => app(GlobalActivityAccess::class)->revoke($activity, $foreign->id))->toThrow(ModelNotFoundException::class);
    $user->update(['is_active' => false]);
    expect(app(GlobalActivityAccess::class)->canManage($user, $activity))->toBeFalse();
});
