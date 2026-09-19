<?php

use App\Jobs\SendActivityAccessLink;
use App\Jobs\SendActivityNotification;
use App\Livewire\Activities\GlobalParticipants;
use App\Mail\OutboundMessage;
use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\ActivityEntry;
use App\Models\ActivityMessageDelivery;
use App\Models\ActivityRegistration;
use App\Models\Announcement;
use App\Models\MessagingSetting;
use App\Models\School;
use App\Models\User;
use App\Services\MessagingService;
use App\Services\NotificationService;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Http::preventStrayRequests();
    Queue::fake([SendActivityNotification::class]);
});

test('activity updates notify only active registered participants of the exact activity', function () {
    $activity = Activity::factory()->publicRegistration()->create();
    $entry = ActivityEntry::factory()->create(['activity_id' => $activity->id]);
    $member = ActivityRegistration::factory()->create(['entry_id' => $entry->id]);
    ActivityRegistration::factory()->create(['entry_id' => $entry->id, 'status' => 'revoked']);
    ActivityRegistration::factory()->create();
    $inactive = ActivityEntry::factory()->create(['activity_id' => $activity->id, 'status' => 'inactive']);
    ActivityRegistration::factory()->create(['entry_id' => $inactive->id]);

    $activity->update(['location' => 'Lapangan baru', 'start_at' => now()->addDays(2)]);

    Queue::assertPushed(SendActivityNotification::class, 1);
    $delivery = ActivityMessageDelivery::sole();
    expect($delivery->activity_registration_id)->toBe($member->id)
        ->and($delivery->body)->toContain('Lapangan baru', 'Waktu mulai')
        ->and($delivery->channel)->toBe('email');
    $activity->save();
    Queue::assertPushed(SendActivityNotification::class, 1);
});

test('rolled back activity changes do not retain deliveries', function () {
    $member = ActivityRegistration::factory()->create();
    DB::beginTransaction();
    $member->activity->update(['location' => 'Dibatalkan']);
    DB::rollBack();
    $this->assertDatabaseCount('activity_message_deliveries', 0);
});

test('approval notifies only the approved entry once', function () {
    $member = ActivityRegistration::factory()->create();
    ActivityRegistration::factory()->create(['activity_id' => $member->activity_id]);
    $member->entry->update(['validation_status' => 'validated']);
    $member->entry->update(['validation_status' => 'validated']);
    expect(ActivityMessageDelivery::sole()->activity_registration_id)->toBe($member->id);
    Queue::assertPushed(SendActivityNotification::class, 1);
});

test('organizer sends related announcements to activity participants and sees delivery history', function () {
    $member = ActivityRegistration::factory()->create();
    ActivityRegistration::factory()->create();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));

    Livewire::test(GlobalParticipants::class, ['activityId' => $member->activity_id])
        ->call('sendAnnouncement')->assertHasErrors(['notificationTitle', 'notificationBody'])
        ->set('notificationTitle', 'Technical meeting')->set('notificationBody', 'Hadir pukul 08.00.')
        ->call('sendAnnouncement')->assertHasNoErrors()->assertSee('Technical meeting')->assertSee('Menunggu pengiriman');

    expect(ActivityMessageDelivery::sole()->activity_registration_id)->toBe($member->id);
});

test('ordinary users cannot broadcast participant announcements', function () {
    $activity = Activity::factory()->publicRegistration()->create();
    $this->actingAs(User::factory()->create(['system_role' => 'student']));
    Livewire::test(GlobalParticipants::class, ['activityId' => $activity->id])->assertForbidden();
    Queue::assertNothingPushed();
});

test('participant mail works without an account and does not rotate the access token', function () {
    MessagingSetting::factory()->create(['channel' => 'email', 'enabled' => true, 'options' => [
        'host' => 'smtp.example.com', 'port' => 587, 'from_address' => 'mail@example.com',
    ]]);
    Mail::fake();
    $member = ActivityRegistration::factory()->create(['user_id' => null, 'token_hash' => hash('sha256', 'original')]);
    $delivery = ActivityMessageDelivery::factory()->create(['activity_registration_id' => $member->id]);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $job = new SendActivityNotification($delivery->id);

    $job->handle(app(MessagingService::class));
    $job->handle(app(MessagingService::class));

    Mail::assertSent(OutboundMessage::class, fn ($mail) => $mail->hasTo($member->destination)
        && str_contains($mail->render(), 'Lokasi kegiatan diperbarui.'));
    Mail::assertSentCount(1);
    expect($delivery->fresh()->status)->toBe('sent')
        ->and($member->fresh()->token_hash)->toBe(hash('sha256', 'original'))
        ->and(app(SchoolContext::class)->id())->toBe($school->id);
});

test('cancelled activities still notify participants through whatsapp without retrying failures', function (bool $success) {
    MessagingSetting::factory()->create(['channel' => 'whatsapp', 'enabled' => true, 'options' => ['token' => 'secret']]);
    Http::fake(['https://api.fonnte.com/send' => Http::response(['status' => $success])]);
    $member = ActivityRegistration::factory()->create(['channel' => 'whatsapp', 'destination' => '628123456789']);
    $member->activity->update(['status' => 'cancelled']);
    $delivery = ActivityMessageDelivery::sole();
    $job = new SendActivityNotification($delivery->id);

    $job->handle(app(MessagingService::class));
    $job->handle(app(MessagingService::class));

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => str_contains($request['message'], 'Dibatalkan'));
    expect($delivery->fresh()->status)->toBe($success ? 'sent' : 'failed');
})->with([true, false]);

test('queued updates skip recipients revoked before delivery', function () {
    $delivery = ActivityMessageDelivery::factory()->create();
    $delivery->registration->update(['status' => 'revoked']);
    (new SendActivityNotification($delivery->id))->handle(app(MessagingService::class));
    expect($delivery->fresh()->status)->toBe('skipped');
    Http::assertNothingSent();
});

test('global announcements never dispatch notifications even through notification service', function () {
    Queue::fake();
    $announcement = Announcement::create(['title' => 'Global', 'body' => 'Informasi', 'status' => 'published', 'created_by' => User::factory()->create()->id]);
    app(NotificationService::class)->publish($announcement);
    Queue::assertNothingPushed();
    $this->assertDatabaseCount('notification_logs', 0);
});

test('activity access emails use the structured message layout', function () {
    MessagingSetting::factory()->create(['channel' => 'email', 'enabled' => true, 'options' => [
        'host' => 'smtp.example.com', 'port' => 587, 'from_address' => 'mail@example.com',
    ]]);
    Mail::fake();
    $member = ActivityRegistration::factory()->create();
    (new SendActivityAccessLink($member->id, $member->access_version))->handle(app(MessagingService::class));
    expect($member->fresh()->delivery_status)->toBe('sent');
    Mail::assertSent(OutboundMessage::class, fn ($mail) => str_contains($mail->render(), '/akses-kegiatan/'));
});

test('publishing competition results notifies participants with a public results link', function (bool $public) {
    $activity = Activity::factory()->publicRegistration()->create(['is_public' => $public]);
    $entry = ActivityEntry::factory()->create(['activity_id' => $activity->id]);
    $member = ActivityRegistration::factory()->create(['entry_id' => $entry->id]);
    $assessment = ActivityAssessment::factory()->special()->published()->create(['school_id' => null, 'activity_id' => $activity->id]);

    $assessment->update(['results_published_at' => now()]);

    if ($public) {
        $delivery = ActivityMessageDelivery::sole();
        expect($delivery->activity_registration_id)->toBe($member->id)
            ->and($delivery->body)->toContain(route('public.activities.results', ['activityId' => $activity->id, 'assessmentId' => $assessment->id]));
    } else {
        $this->assertDatabaseCount('activity_message_deliveries', 0);
        Queue::assertNothingPushed();
    }
})->with([true, false]);
