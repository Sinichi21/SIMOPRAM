<?php

use App\Livewire\Activities\Index;
use App\Livewire\Admin\PublicContent;
use App\Models\Activity;
use App\Models\ActivityDelegate;
use App\Models\User;
use App\Services\ActivityHierarchyService;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('global agenda contains independent subactivities with navigation in both directions', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $parent = Activity::factory()->publicRegistration()->create(['title' => 'Pandu Trisma Cup']);
    Livewire::test(PublicContent::class)->set('parentActivityId', $parent->id)->set('title', 'Lomba Pionering')
        ->set('body', 'Panduan pionering')->set('startsAt', now()->toDateTimeString())->set('endsAt', now()->addHour()->toDateTimeString())
        ->set('status', 'published')->call('save')->assertHasNoErrors()->assertSet('parentActivityId', null);
    $child = Activity::withoutGlobalScope('school')->where('title', 'Lomba Pionering')->firstOrFail();
    expect($child->parent_activity_id)->toBe($parent->id)->and($child->school_id)->toBeNull()->and($child->registration_open)->toBeFalse();
    $this->get(route('public.activities.show', $parent))->assertOk()->assertSee('Subagenda / cabang kegiatan')->assertSee('Lomba Pionering')->assertSee(route('public.activities.show', $child), false);
    $this->get(route('public.activities.show', $child))->assertOk()->assertSee('Agenda induk: Pandu Trisma Cup');
    Livewire::test(PublicContent::class)->call('edit', $child->id)->assertSet('parentActivityId', $parent->id)->set('title', 'Lomba Bivak')->call('save')->assertHasNoErrors();
    expect($child->fresh()->parent_activity_id)->toBe($parent->id);
});

test('hierarchy rejects self parenting deeper levels and moving an existing parent below another', function (string $case) {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $parent = Activity::factory()->publicRegistration()->create();
    $child = Activity::factory()->publicRegistration()->create(['parent_activity_id' => $parent->id]);
    $other = Activity::factory()->publicRegistration()->create();
    [$subject,$parentId] = match ($case) {
        'self' => [$other, $other->id], 'deeper' => [$other, $child->id], 'existing-parent' => [$parent, $other->id],
    };
    expect(fn () => DB::transaction(fn () => app(ActivityHierarchyService::class)->assignParent($subject, $parentId)))->toThrow(ValidationException::class);
    expect($subject->fresh()->parent_activity_id)->toBeNull();
})->with(['self', 'deeper', 'existing-parent']);

test('public subagenda list excludes unpublished private scheduled and foreign school records', function (array $attributes) {
    $parent = Activity::factory()->publicRegistration()->create();
    Activity::factory()->publicRegistration()->create(['parent_activity_id' => $parent->id, 'title' => 'Subagenda tersembunyi', ...$attributes]);
    $this->get(route('public.activities.show', $parent))->assertOk()->assertDontSee('Subagenda tersembunyi');
})->with([
    'draft' => [['status' => 'draft']], 'private' => [['is_public' => false]], 'scheduled' => [['published_at' => now()->addDay()]],
    'pending' => [['approval_status' => 'pending']], 'cancelled' => [['status' => 'cancelled']],
]);

test('school agenda supports subagenda but cannot select a different school or global parent', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $parent = Activity::factory()->create(['status' => 'published', 'is_public' => true]);
    $foreign = Activity::factory()->create();
    $global = Activity::factory()->publicRegistration()->create();
    app(SchoolContext::class)->set($parent->school);
    $component = Livewire::test(Index::class)->set('academic_year_id', $parent->academic_year_id)->set('activity_type', 'competition')
        ->set('title', 'Lomba Sekolah')->set('start_at', now()->toDateTimeString())->set('end_at', now()->addHour()->toDateTimeString())
        ->set('parentActivityId', $foreign->id)->call('save')->assertHasErrors('parentActivityId');
    $component->set('parentActivityId', $global->id)->call('save')->assertHasErrors('parentActivityId')
        ->set('parentActivityId', $parent->id)->set('status', 'published')->set('is_public', true)->call('save')->assertHasNoErrors();
    $child = Activity::where('title', 'Lomba Sekolah')->firstOrFail();
    expect($child->parent_activity_id)->toBe($parent->id);
    $this->get(route('schools.activities.show', [$parent->school, $parent]))->assertOk()->assertSee('Lomba Sekolah');
    $this->get(route('schools.activities.show', [$parent->school, $child]))->assertOk()->assertSee('Agenda induk: '.$parent->title);
});

test('a subagenda delegate retains editing access without receiving access to its parent or siblings', function () {
    $parent = Activity::factory()->publicRegistration()->create(['title' => 'Induk privat pengelola']);
    $child = Activity::factory()->publicRegistration()->create(['parent_activity_id' => $parent->id, 'description' => 'Deskripsi lomba']);
    $other = Activity::factory()->publicRegistration()->create();
    $user = User::factory()->create();
    ActivityDelegate::factory()->create(['activity_id' => $child->id, 'user_id' => $user->id]);
    Livewire::actingAs($user)->test(PublicContent::class)->call('edit', $child->id)->set('title', 'Lomba diperbarui')->call('save')->assertHasNoErrors();
    $this->get(route('admin.activity-participants', $parent))->assertForbidden();
    Livewire::test(PublicContent::class)->call('edit', $child->id)->set('parentActivityId', $other->id)->call('save')->assertForbidden();
    expect($child->fresh()->parent_activity_id)->toBe($parent->id);
});

test('a public child does not disclose a private parent and can become standalone', function () {
    $parent = Activity::factory()->publicRegistration()->create(['title' => 'Agenda induk rahasia', 'is_public' => false]);
    $child = Activity::factory()->publicRegistration()->create(['parent_activity_id' => $parent->id, 'description' => 'Cabang lomba']);
    $this->get(route('public.activities.show', $child))->assertOk()->assertDontSee('Agenda induk rahasia');
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    Livewire::test(PublicContent::class)->call('edit', $child->id)->set('parentActivityId', null)->call('save')->assertHasNoErrors();
    expect($child->fresh()->parent_activity_id)->toBeNull();
});
