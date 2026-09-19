<?php

use App\Jobs\SendAnnouncementNotification;
use App\Livewire\Messaging\Settings;
use App\Livewire\NotificationSettings\Manage;
use App\Mail\OutboundMessage;
use App\Messaging\TextMessage;
use App\Models\Announcement;
use App\Models\Coach;
use App\Models\MessagingSetting;
use App\Models\NotificationLog;
use App\Models\School;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Services\MessagingService;
use App\Services\NotificationService;
use App\Services\TelegramLinkService;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.telegram.bot_token' => null, 'services.telegram.bot_username' => null]);
    Http::preventStrayRequests();
});

test('only super admins may access integration settings', function (string $role) {
    $this->actingAs(User::factory()->create(['system_role' => $role, 'is_active' => true]));

    $this->get(route('settings.messaging'))->assertForbidden();
    Livewire::test(Settings::class)->assertForbidden();
    $this->assertDatabaseCount('messaging_settings', 0);
})->with(['student', 'coach', 'school_admin', 'scout_admin']);

test('guests must sign in before opening integration settings', function () {
    $this->get(route('settings.messaging'))->assertRedirect(route('login'));
});

test('super admin saves encrypted credentials without exposing stored secrets', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]));
    $this->get(route('settings.messaging'))->assertOk()->assertSee('Integrasi Pesan');

    Livewire::test(Settings::class)->set('whatsappEnabled', true)->set('fonnteToken', 'fonnte-secret')
        ->set('telegramEnabled', true)->set('telegramToken', '123456:telegram-secret')->set('telegramUsername', 'simpram_bot')
        ->set('emailEnabled', true)->set('host', 'smtp.example.com')->set('username', 'mailer')
        ->set('password', 'smtp-secret')->set('fromAddress', 'school@example.com')->call('save')
        ->assertHasNoErrors()->assertSet('fonnteToken', '')->assertSet('telegramToken', '')->assertSet('password', '');

    expect(DB::table('messaging_settings')->where('channel', 'whatsapp')->value('options'))->not->toContain('fonnte-secret');
    expect(MessagingSetting::forChannel('email')->toJson())->not->toContain('smtp-secret');
    Livewire::test(Settings::class)->assertSet('fonnteToken', '')->assertSet('telegramToken', '')
        ->assertSet('password', '')->call('save')->assertHasNoErrors();
    expect(MessagingSetting::forChannel('whatsapp')->options['token'])->toBe('fonnte-secret');
    expect(MessagingSetting::forChannel('email')->options['password'])->toBe('smtp-secret');
});

test('integration settings require credentials and valid smtp fields', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]));
    Livewire::test(Settings::class)->set('whatsappEnabled', true)->call('save')->assertHasErrors('fonnteToken');
    Livewire::test(Settings::class)->set('telegramEnabled', true)->set('telegramUsername', 'simpram_bot')
        ->call('save')->assertHasErrors('telegramToken');
    Livewire::test(Settings::class)->set('emailEnabled', true)->set('port', '70000')
        ->set('scheme', 'http')->call('save')->assertHasErrors(['host', 'fromAddress', 'port', 'scheme']);
    $this->assertDatabaseCount('messaging_settings', 0);
});

test('fonnte receives one normalized recipient and no bearer prefix', function (string $number) {
    MessagingSetting::factory()->create(['enabled' => true, 'options' => ['token' => 'fonnte-secret']]);
    Http::fake(['https://api.fonnte.com/send' => Http::response(['status' => true])]);

    expect(app(MessagingService::class)->send('whatsapp', $number, 'Pesan contoh'))->toBe('accepted');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'fonnte-secret')
        && $request['target'] === '6281234567890' && $request['message'] === 'Pesan contoh'
        && $request['countryCode'] === '0');
    Http::assertSentCount(1);
})->with(['081234567890', '+62 812-3456-7890', '6281234567890']);

test('fonnte errors are sanitized and never automatically resent', function (int $status, array $body) {
    MessagingSetting::factory()->create(['enabled' => true, 'options' => ['token' => 'fonnte-secret']]);
    Http::fake(['https://api.fonnte.com/send' => Http::response($body, $status)]);

    expect(fn () => app(MessagingService::class)->send('whatsapp', '081234567890', 'Private link'))
        ->toThrow(RuntimeException::class, 'Pengiriman belum terkonfirmasi.');
    Http::assertSentCount(1);
})->with([[200, ['status' => false, 'reason' => 'fonnte-secret']], [401, ['status' => false]], [500, []]]);

test('disabled channels and multiple recipient injection do not call providers', function () {
    MessagingSetting::factory()->create();
    expect(fn () => app(MessagingService::class)->send('whatsapp', '081234567890', 'Test'))->toThrow(RuntimeException::class);
    MessagingSetting::forChannel('whatsapp')->update(['enabled' => true, 'options' => ['token' => 'secret']]);
    expect(fn () => app(MessagingService::class)->send('whatsapp', '081234567890,081234567891', 'Test'))
        ->toThrow(ValidationException::class);
    Http::assertNothingSent();
});

test('test sending uses saved credentials and limits repeated sends', function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]));
    MessagingSetting::factory()->create(['enabled' => true, 'options' => ['token' => 'saved-token']]);
    Http::fake(['https://api.fonnte.com/send' => Http::response(['status' => true])]);
    $component = Livewire::test(Settings::class)->set('testDestination', '081234567890');
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $component->call('sendTest')->assertHasNoErrors();
    }
    $component->call('sendTest')->assertHasErrors('testDestination');
    Http::assertSentCount(3);
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'saved-token'));
});

test('telegram resolves linked usernames within the active school', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create();
    MessagingSetting::factory()->create(['channel' => 'telegram', 'enabled' => true, 'options' => ['token' => '123:secret']]);
    UserNotificationChannel::query()->create(['user_id' => $user->id, 'channel' => 'telegram',
        'destination' => '123456789', 'is_verified' => true, 'is_active' => true, 'metadata' => ['username' => 'CoachOne']]);
    Http::fake(['https://api.telegram.org/bot123:secret/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 42]])]);

    expect(app(MessagingService::class)->send('telegram', '@coachone', 'Undangan'))->toBe('message_id=42');
    Http::assertSent(fn ($request) => $request['chat_id'] === '123456789' && $request['text'] === 'Undangan');
    app(SchoolContext::class)->set(School::factory()->create());
    expect(fn () => app(MessagingService::class)->send('telegram', '@coachone', 'Other school'))->toThrow(RuntimeException::class);
    Http::assertSentCount(1);
});

test('telegram links use configured bot username and respect disabled bot', function () {
    app(SchoolContext::class)->set(School::factory()->create());
    $user = User::factory()->create();
    MessagingSetting::factory()->create(['channel' => 'telegram', 'enabled' => true,
        'options' => ['token' => '123:secret', 'username' => 'school_bot']]);
    expect(app(TelegramLinkService::class)->createLink($user))->toStartWith('https://t.me/school_bot?start=');
    MessagingSetting::forChannel('telegram')->update(['enabled' => false]);
    expect(fn () => app(TelegramLinkService::class)->createLink($user))->toThrow(ValidationException::class);
});

test('email uses saved smtp settings and keeps message content escaped', function () {
    MessagingSetting::factory()->create(['channel' => 'email', 'enabled' => true, 'options' => [
        'host' => 'smtp.example.com', 'port' => 465, 'scheme' => 'smtps', 'username' => 'mailer',
        'password' => 'secret', 'from_address' => 'school@example.com', 'from_name' => 'Sekolah',
    ]]);
    Mail::fake();

    app(MessagingService::class)->send('email', 'recipient@example.com', '<script>unsafe</script>', 'Pengumuman');

    Mail::assertSent(OutboundMessage::class, fn ($mail) => $mail->hasTo('recipient@example.com')
        && $mail->envelope()->subject === 'Pengumuman' && str_contains($mail->render(), '&lt;script&gt;unsafe&lt;/script&gt;'));
    expect(config('mail.mailers.messaging.host'))->toBe('smtp.example.com');
    expect(config('mail.from.address'))->toBe('school@example.com');
    expect((new OutboundMessage(new TextMessage('<script>unsafe</script>', 'Pengumuman')))->render())
        ->toContain('&lt;script&gt;unsafe&lt;/script&gt;')->not->toContain('<script>unsafe</script>');
});

test('disabled email blocks password reset notification', function () {
    MessagingSetting::factory()->create(['channel' => 'email', 'enabled' => false]);
    Notification::fake();
    $user = User::factory()->create();
    expect(fn () => $user->sendPasswordResetNotification('token'))->toThrow(RuntimeException::class);
    Notification::assertNothingSent();
});

test('users save their own notification destinations per school', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($user)->withSession(['active_school_id' => $school->id]);
    app(SchoolContext::class)->set($school);
    Livewire::test(Manage::class)->set('whatsappNumber', '081234567890')->set('whatsappEnabled', true)
        ->set('emailEnabled', true)->call('savePreferences')->assertHasNoErrors();
    $this->assertDatabaseHas('user_notification_channels', ['school_id' => $school->id, 'user_id' => $user->id,
        'channel' => 'whatsapp', 'destination' => '6281234567890', 'is_active' => true]);
    $this->assertDatabaseHas('user_notification_channels', ['school_id' => $school->id, 'user_id' => $user->id,
        'channel' => 'email', 'destination' => $user->email]);
    app(SchoolContext::class)->set($otherSchool);
    session(['active_school_id' => $otherSchool->id]);
    Livewire::test(Manage::class)->assertSet('whatsappEnabled', false)->assertSet('emailEnabled', false);
    $this->assertDatabaseCount('user_notification_channels', 2);
});

test('invalid whatsapp preference does not persist any channel', function () {
    $school = School::factory()->create();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]));
    app(SchoolContext::class)->set($school);
    session(['active_school_id' => $school->id]);
    Livewire::test(Manage::class)->set('whatsappEnabled', true)->set('whatsappNumber', 'not-a-phone')
        ->call('savePreferences')->assertHasErrors('whatsappNumber');
    $this->assertDatabaseCount('user_notification_channels', 0);
});

test('students and coaches can configure their own email and whatsapp notifications', function (string $role) {
    $school = School::factory()->create();
    $user = User::factory()->create(['system_role' => $role, 'is_active' => true, 'approval_status' => 'approved']);
    $user->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true, 'joined_at' => now()]);
    $this->actingAs($user)->withSession(['active_school_id' => $school->id]);
    app(SchoolContext::class)->set($school);

    $this->get(route('notification-settings.manage'))->assertOk()->assertSee('Email & WhatsApp saya', false)->assertSee($user->email);
    Livewire::test(Manage::class)->set('whatsappNumber', '081234567890')->set('whatsappEnabled', true)
        ->set('emailEnabled', true)->call('savePreferences')->assertHasNoErrors();

    $this->assertDatabaseHas('user_notification_channels', ['school_id' => $school->id, 'user_id' => $user->id,
        'channel' => 'email', 'destination' => $user->email, 'is_active' => true]);
    $this->assertDatabaseHas('user_notification_channels', ['school_id' => $school->id, 'user_id' => $user->id,
        'channel' => 'whatsapp', 'destination' => '6281234567890', 'is_active' => true]);
})->with(['student', 'coach']);

test('announcement email uses its dedicated template and honors the user preference', function (bool $enabled) {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create(['is_active' => true]);
    $announcement = Announcement::create(['title' => 'Latihan sekolah', 'body' => '<p>Hadir besok pagi.</p>', 'created_by' => $user->id]);
    UserNotificationChannel::create(['user_id' => $user->id, 'channel' => 'email', 'destination' => $user->email, 'is_active' => $enabled]);
    MessagingSetting::factory()->create(['channel' => 'email', 'enabled' => true, 'options' => [
        'host' => 'smtp.example.com', 'port' => 587, 'from_address' => 'mail@example.com',
    ]]);
    Mail::fake();

    (new SendAnnouncementNotification($school->id, $announcement->id, $user->id, 'email'))->handle(app(MessagingService::class));

    if ($enabled) {
        Mail::assertSent(OutboundMessage::class, fn ($mail) => $mail->hasTo($user->email)
            && $mail->content()->view === 'mail.notification.announcement'
            && str_contains($mail->render(), 'Hadir besok pagi.') && str_contains($mail->render(), 'Buka pengumuman saya'));
    } else {
        Mail::assertNothingSent();
    }
    expect(NotificationLog::sole()->status)->toBe($enabled ? 'sent' : 'skipped');
})->with([true, false]);

test('publishing queues each enabled channel only once', function () {
    app(SchoolContext::class)->set(School::factory()->create());
    $user = User::factory()->create(['is_active' => true]);
    Coach::query()->create(['name' => 'Coach', 'user_id' => $user->id, 'is_active' => true]);
    $announcement = Announcement::query()->create(['title' => 'Latihan', 'body' => 'Besok', 'created_by' => $user->id]);
    $announcement->targets()->create(['target_type' => 'all_coaches']);
    foreach (['whatsapp', 'telegram', 'email'] as $channel) {
        MessagingSetting::factory()->create(['channel' => $channel, 'enabled' => true]);
    }
    Queue::fake([SendAnnouncementNotification::class]);

    app(NotificationService::class)->publish($announcement);
    app(NotificationService::class)->publish($announcement);

    Queue::assertPushed(SendAnnouncementNotification::class, 3);
    foreach (['whatsapp', 'telegram', 'email'] as $channel) {
        Queue::assertPushed(SendAnnouncementNotification::class, fn ($job) => $job->channel === $channel && $job->userId === $user->id && $job->afterCommit);
    }
    $this->assertDatabaseCount('notification_logs', 4);
});

test('announcement delivery records success once and restores the previous school context', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create(['is_active' => true]);
    $announcement = Announcement::query()->create(['title' => 'Latihan', 'body' => 'Besok', 'created_by' => $user->id]);
    UserNotificationChannel::query()->create(['user_id' => $user->id, 'channel' => 'whatsapp',
        'destination' => '6281234567890', 'is_active' => true]);
    MessagingSetting::factory()->create(['enabled' => true, 'options' => ['token' => 'secret']]);
    Http::fake(['https://api.fonnte.com/send' => Http::response(['status' => true])]);
    $other = School::factory()->create();
    app(SchoolContext::class)->set($other);
    $job = new SendAnnouncementNotification($school->id, $announcement->id, $user->id, 'whatsapp');

    $job->handle(app(MessagingService::class));
    $job->handle(app(MessagingService::class));

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => str_contains($request['message'], route('announcements.my')));
    $this->assertDatabaseHas('notification_logs', ['school_id' => $school->id, 'channel' => 'whatsapp', 'status' => 'sent']);
    expect(app(SchoolContext::class)->id())->toBe($other->id);
});

test('announcement failure is logged without leaking provider response or retrying', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create(['is_active' => true]);
    $announcement = Announcement::query()->create(['title' => 'Latihan', 'body' => 'Besok', 'created_by' => $user->id]);
    UserNotificationChannel::query()->create(['user_id' => $user->id, 'channel' => 'whatsapp',
        'destination' => '6281234567890', 'is_active' => true]);
    MessagingSetting::factory()->create(['enabled' => true, 'options' => ['token' => 'private-token']]);
    Http::fake(['https://api.fonnte.com/send' => Http::response(['status' => false, 'reason' => 'private-token'])]);
    $job = new SendAnnouncementNotification($school->id, $announcement->id, $user->id, 'whatsapp');

    $job->handle(app(MessagingService::class));
    $job->handle(app(MessagingService::class));

    Http::assertSentCount(1);
    expect(NotificationLog::query()->first()->status)->toBe('failed');
    expect(NotificationLog::query()->first()->error_message)->not->toContain('private-token');
});
