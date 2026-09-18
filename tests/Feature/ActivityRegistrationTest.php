<?php

use App\Jobs\SendActivityAccessLink;
use App\Livewire\Activities\GlobalParticipants;
use App\Livewire\Activities\RegistrationSettings;
use App\Livewire\Admin\PublicContent;
use App\Livewire\Assessments\Activities\GlobalManage;
use App\Models\Activity;
use App\Models\ActivityEntry;
use App\Models\ActivityRegistration;
use App\Models\Announcement;
use App\Models\MessagingSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityRegistrationService;
use App\Services\ContentMediaService;
use App\Services\MessagingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** @return array<string, mixed> */
function externalActivityPerson(string $name): array
{
    return ['source' => 'external', 'name' => $name, 'identifier' => 'NTA-'.$name,
        'school_name' => 'Sekolah Asal', 'channel' => 'email', 'destination' => str_replace(' ', '', strtolower($name)).'@example.com'];
}

/** @return array<string, mixed> */
function activityEntryPayload(string $category = 'individual', int $count = 1): array
{
    return ['category' => $category, 'name' => 'Kelompok Garuda',
        'members' => array_map(fn (int $index): array => externalActivityPerson('Anggota'.$index), range(1, $count)),
        'coach' => externalActivityPerson('Pembina'), 'declaration' => '1', 'terms' => '1', 'answers' => []];
}

test('public registration creates one entry with one coach and optional reserve then awaits validation', function () {
    Queue::fake([SendActivityAccessLink::class]);
    $activity = Activity::factory()->publicRegistration()->create();
    $this->get(route('public.activities.register', $activity))->assertOk()->assertSee('SIMPRAM')->assertSee('syarat dan ketentuan');
    $data = activityEntryPayload();
    $data['reserve'] = externalActivityPerson('Cadangan');
    $this->post(route('public.activities.register.store', $activity), $data)->assertRedirect(route('public.activities.participants', $activity))->assertSessionHasNoErrors();
    $entry = $activity->entries()->firstOrFail();
    expect($entry->name)->toBe('Anggota1')->and($entry->validation_status)->toBe('pending')->and($entry->members()->count())->toBe(3)
        ->and($entry->members()->where('role', 'coach')->count())->toBe(1)->and($entry->members()->where('is_reserve', true)->count())->toBe(1);
    Queue::assertPushed(SendActivityAccessLink::class, 3);
    $this->assertDatabaseCount('students', 0);
    $this->get(route('public.activities.participants', $activity))->assertDontSee('Anggota1');
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    Livewire::test(GlobalParticipants::class, ['activityId' => $activity->id])->call('validateEntry', $entry->id)->call('detail', $entry->id)->assertSee('Menunggu pengiriman')->assertHasNoErrors();
    $this->get(route('public.activities.participants', $activity))->assertSee('Anggota1')->assertSee('NTA-Anggota1')->assertSee('Sekolah Asal')->assertDontSee('anggota1@example.com');
});

test('group size boundaries count primary students separately from coach and reserve', function (string $category, int $count, bool $valid) {
    Queue::fake([SendActivityAccessLink::class]);
    $activity = Activity::factory()->publicRegistration()->create(['registration_categories' => [$category], 'team_min' => 2, 'team_max' => 3]);
    $response = $this->post(route('public.activities.register.store', $activity), activityEntryPayload($category, $count));
    if ($valid) {
        $response->assertSessionHasNoErrors()->assertRedirect(route('public.activities.participants', $activity));
        expect($activity->registrations()->count())->toBe($count + 1);
        Queue::assertPushed(SendActivityAccessLink::class, $count + 1);
    } else {
        $response->assertSessionHasErrors('members');
        $this->assertDatabaseCount('activity_entries', 0);
        Queue::assertNotPushed(SendActivityAccessLink::class);
    }
})->with([
    'individual maximum' => ['individual', 2, false], 'barung minimum' => ['siaga', 4, true], 'barung maximum' => ['siaga', 6, true],
    'barung too few' => ['siaga', 3, false], 'barung too many' => ['siaga', 7, false],
    'regu minimum' => ['penggalang', 6, true], 'regu maximum' => ['penggalang', 8, true], 'regu too few' => ['penggalang', 5, false], 'regu too many' => ['penggalang', 9, false],
    'sangga minimum' => ['penegak', 4, true], 'sangga maximum' => ['penegak', 8, true], 'sangga too few' => ['penegak', 3, false], 'sangga too many' => ['penegak', 9, false],
    'team minimum' => ['team', 2, true], 'team maximum' => ['team', 3, true], 'team too few' => ['team', 1, false], 'team too many' => ['team', 4, false],
]);

test('mandatory acknowledgements coach and duplicate members are validated before registration', function (string $case, string $error) {
    Queue::fake([SendActivityAccessLink::class]);
    $activity = Activity::factory()->publicRegistration()->create();
    $data = activityEntryPayload();
    match ($case) {
        'terms' => $data['terms'] = '0', 'declaration' => $data['declaration'] = '0',
        'coach' => $data['coach'] = [], 'duplicate' => $data['reserve'] = $data['members'][0],
        'unknown' => $data['answers'] = ['secret' => 'Injected'],
    };
    $this->post(route('public.activities.register.store', $activity), $data)->assertSessionHasErrors($error);
    $this->assertDatabaseCount('activity_entries', 0);
})->with([['terms', 'terms'], ['declaration', 'declaration'], ['coach', 'coach'], ['duplicate', 'members'], ['unknown', 'answers']]);

test('registration button and endpoints honor the open setting and activity lifetime', function (array $attributes) {
    $activity = Activity::factory()->publicRegistration()->create($attributes);
    $this->get(route('public.activities.register', $activity))->assertForbidden();
    $this->post(route('public.activities.register.store', $activity), activityEntryPayload())->assertForbidden();
    $this->get(route('public.activities.show', $activity))->assertDontSee('Registrasi peserta');
})->with([[['registration_open' => false]], [['end_at' => now()->subMinute()]], [['status' => 'completed']]]);

test('form settings save every field type but reject duplicated identity data and multiple upload fields', function () {
    $activity = Activity::factory()->publicRegistration()->create();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $fields = collect(['short_text', 'paragraph', 'radio', 'checkbox', 'select', 'date', 'time', 'file'])->map(fn (string $type, int $index): array => [
        'id' => 'field'.$index, 'label' => 'Pertanyaan '.$index, 'description' => '', 'type' => $type, 'required' => true, 'options_text' => "A\nB",
    ])->all();
    $component = Livewire::test(RegistrationSettings::class, ['activityId' => $activity->id])->set('fields', $fields)->call('save')->assertHasNoErrors();
    expect($activity->fresh()->registration_fields)->toHaveCount(8);
    $this->get(route('public.activities.register', $activity))->assertOk()->assertSee('Pertanyaan 7');
    $component->set('fields.0.label', 'Nama peserta')->call('save')->assertHasErrors('fields.0.label');
    $component->set('fields.0.label', 'Pilihan lomba')->set('fields.0.type', 'file')->call('save')->assertHasErrors('fields');
    $component->set('fields.0.type', 'short_text')->set('fields.0.label', '')->call('save')->assertHasErrors('fields.0.label');
    $this->actingAs(User::factory()->create());
    Livewire::test(RegistrationSettings::class, ['activityId' => $activity->id])->assertForbidden();
});

test('custom answers and private documents are persisted with their original form and only managers can download', function () {
    Queue::fake([SendActivityAccessLink::class]);
    Storage::fake('local');
    $activity = Activity::factory()->publicRegistration()->create(['registration_fields' => [
        ['id' => 'choice', 'label' => 'Cabang lomba', 'description' => '', 'type' => 'checkbox', 'required' => true, 'options' => ['Pionering', 'Sandi']],
        ['id' => 'document', 'label' => 'Surat izin', 'description' => '', 'type' => 'file', 'required' => true, 'options' => []],
    ]]);
    $data = activityEntryPayload();
    $data['answers'] = ['choice' => ['Sandi']];
    $data['files'] = [UploadedFile::fake()->create('izin.pdf', 100, 'application/pdf')];
    $this->post(route('public.activities.register.store', $activity), $data)->assertSessionHasNoErrors();
    $entry = $activity->entries()->firstOrFail();
    Storage::disk('local')->assertExists($entry->attachments[0]['path']);
    expect($entry->answers)->toBe(['choice' => ['Sandi']]);
    $activity->forceFill(['registration_fields' => []])->save();
    $this->get(route('admin.activity-participants.attachment', [$activity->id, $entry->id, 0]))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $this->get(route('admin.activity-participants.edit', [$activity->id, $entry->id]))->assertOk()->assertSee('Cabang lomba')->assertSee('Surat izin');
    $this->get(route('admin.activity-participants.attachment', [$activity->id, $entry->id, 0]).'?download=1')->assertDownload('izin.pdf');
    Livewire::test(GlobalParticipants::class, ['activityId' => $activity->id])->call('detail', $entry->id)->assertSee('Sandi');
    $this->actingAs(User::factory()->create())->get(route('admin.activity-participants.attachment', [$activity->id, $entry->id, 0]))->assertForbidden();
});

test('registration upload rules reject excess count oversize and unconfigured uploads', function (string $case, string $error) {
    $activity = Activity::factory()->publicRegistration()->create(['registration_fields' => $case === 'unconfigured' ? [] : [
        ['id' => 'document', 'label' => 'Dokumen', 'description' => '', 'type' => 'file', 'required' => true, 'options' => []],
    ]]);
    $data = activityEntryPayload();
    $data['files'] = match ($case) {
        'count' => array_map(fn (): UploadedFile => UploadedFile::fake()->create('izin.pdf', 1, 'application/pdf'), range(1, 11)),
        'size' => [UploadedFile::fake()->create('izin.pdf', 5121, 'application/pdf')],
        'missing' => [], default => [UploadedFile::fake()->create('izin.pdf', 1, 'application/pdf')],
    };
    $this->post(route('public.activities.register.store', $activity), $data)->assertSessionHasErrors($error);
    $this->assertDatabaseCount('activity_entries', 0);
})->with([['count', 'files'], ['size', 'files.0'], ['missing', 'files'], ['unconfigured', 'files']]);

test('required dynamic answers use the administrator question label in errors', function () {
    app()->setLocale('id');
    Queue::fake([SendActivityAccessLink::class]);
    $activity = Activity::factory()->publicRegistration()->create(['registration_fields' => [
        ['id' => 'random_question_id', 'label' => 'Pengalaman berkemah', 'description' => '', 'type' => 'paragraph', 'required' => true, 'options' => []],
    ]]);
    $this->post(route('public.activities.register.store', $activity), activityEntryPayload())
        ->assertSessionHasErrors(['answers.random_question_id' => 'Pengalaman berkemah wajib diisi.']);
    $this->assertDatabaseCount('activity_entries', 0);
    Queue::assertNothingPushed();
});

test('SIMPRAM selection uses canonical data and cannot enroll another users profile', function () {
    Queue::fake([SendActivityAccessLink::class]);
    $activity = Activity::factory()->publicRegistration()->create();
    $student = Student::factory()->create(['name' => 'Nama Resmi']);
    $user = User::factory()->create(['email' => 'resmi@example.com']);
    $student->update(['user_id' => $user->id]);
    $user->schoolMemberships()->create(['school_id' => $student->school_id, 'is_active' => true]);
    $data = activityEntryPayload();
    $data['members'][0] = ['source' => 'student', 'profile_id' => $student->id, 'channel' => 'email', 'name' => 'Nama Palsu', 'destination' => 'palsu@example.com'];
    $this->actingAs(User::factory()->create())->post(route('public.activities.register.store', $activity), $data)->assertNotFound();
    $this->actingAs($user)->post(route('public.activities.register.store', $activity), $data)->assertSessionHasNoErrors();
    $member = $activity->registrations()->where('student_id', $student->id)->firstOrFail();
    expect($member->name)->toBe('Nama Resmi')->and($member->destination)->toBe('resmi@example.com')->and($member->user_id)->toBe($user->id);
    $this->assertDatabaseCount('students', 1);
    $this->post(route('public.activities.register.store', $activity), $data)->assertSessionHasErrors('registration');
    $this->get(route('activity-access.mine'))->assertOk()->assertSee($activity->title);
});

test('SIMPRAM participants without accounts can use a delivery contact without duplicate profiles', function (string $channel, string $contact, string $expected) {
    Queue::fake([SendActivityAccessLink::class]);
    $activity = Activity::factory()->publicRegistration()->create();
    $student = Student::factory()->create(['user_id' => null, 'phone' => null, 'parent_phone' => null]);
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $userCount = User::count();
    $data = activityEntryPayload();
    $data['members'] = [['source' => 'student', 'profile_id' => $student->id, 'channel' => $channel, 'destination' => $contact]];
    $this->actingAs($admin)->post(route('admin.activity-participants.store', $activity), $data)->assertSessionHasNoErrors();
    $member = $activity->registrations()->where('student_id', $student->id)->firstOrFail();
    expect($member->destination)->toBe($expected)->and($member->user_id)->toBeNull()->and($member->name)->toBe($student->name);
    $this->assertDatabaseCount('students', 1);
    $this->assertDatabaseCount('users', $userCount);
    Queue::assertPushed(SendActivityAccessLink::class, 2);
})->with([['email', 'wali@example.com', 'wali@example.com'], ['whatsapp', '081234567890', '6281234567890']]);

test('missing SIMPRAM delivery contact identifies the person with a readable validation error', function () {
    Queue::fake([SendActivityAccessLink::class]);
    $activity = Activity::factory()->publicRegistration()->create();
    $student = Student::factory()->create(['user_id' => null, 'name' => 'Siswa Tanpa Akun']);
    $data = activityEntryPayload();
    $data['members'] = [['source' => 'student', 'profile_id' => $student->id, 'channel' => 'email']];
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->post(route('admin.activity-participants.store', $activity), $data)
        ->assertSessionHasErrors(['destination' => 'Isi email yang valid untuk Siswa Tanpa Akun. Jika kontak belum tersedia di SIMPRAM, isi kontak penerima akses pada form registrasi.']);
    $this->assertDatabaseCount('activity_entries', 0);
    Queue::assertNothingPushed();
});

test('temporary portal is role limited and resend invalidates both old links and sessions', function () {
    Queue::fake([SendActivityAccessLink::class]);
    $this->freezeTime();
    $member = ActivityRegistration::factory()->create(['token_hash' => hash('sha256', $token = str_repeat('a', 64))]);
    $other = ActivityRegistration::factory()->create();
    $this->get(route('activity-access.open', $token))->assertOk()->assertHeader('Referrer-Policy', 'no-referrer');
    $this->post(route('activity-access.enter', $token))->assertRedirect(route('activity-access.portal', $member));
    $this->get(route('activity-access.portal', $member))->assertOk()->assertSee('Menu siswa');
    $this->get(route('activity-access.portal', $other))->assertForbidden();
    $this->get(route('admin.activity-participants', $member->activity_id))->assertRedirect(route('login'));
    $this->post(route('activity-access.check-in', $member))->assertForbidden();
    $member->entry->update(['validation_status' => 'validated']);
    $this->post(route('activity-access.check-in', $member))->assertRedirect();
    expect($member->fresh()->checked_in_at)->not->toBeNull();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    app(ActivityRegistrationService::class)->resend($member);
    expect($member->fresh()->token_hash)->toBeNull()->and($member->fresh()->access_version)->toBe(2);
    Queue::assertPushed(SendActivityAccessLink::class, fn ($job): bool => $job->registrationId === $member->id && $job->version === 2);
    $this->get(route('activity-access.open', $token))->assertNotFound();
    $this->get(route('activity-access.portal', $member))->assertForbidden();
});

test('portal access expires before the start after the end and when entry is inactive', function (string $case) {
    $this->freezeTime();
    $member = ActivityRegistration::factory()->create(['token_hash' => hash('sha256', $token = str_repeat('b', 64))]);
    match ($case) {
        'future' => $member->activity->update(['start_at' => now()->addHour()]),
        'expired' => $member->activity->update(['end_at' => now()]),
        'inactive' => $member->entry->update(['status' => 'inactive']),
    };
    $this->get(route('activity-access.open', $token))->assertGone();
})->with(['future', 'expired', 'inactive']);

test('delivery job sends direct access but persists no raw token and stale queued jobs send nothing', function () {
    Http::preventStrayRequests();
    Http::fake(['https://api.fonnte.com/send' => Http::response(['status' => true])]);
    MessagingSetting::create(['channel' => 'whatsapp', 'enabled' => true, 'options' => ['token' => 'test-token']]);
    $member = ActivityRegistration::factory()->create(['channel' => 'whatsapp', 'destination' => '628123456789']);
    (new SendActivityAccessLink($member->id, 1))->handle(app(MessagingService::class));
    $message = Http::recorded()[0][0]['message'];
    preg_match('~/akses-kegiatan/([a-zA-Z0-9]{64})~', $message, $matches);
    expect($matches)->toHaveCount(2)->and($member->fresh()->token_hash)->toBe(hash('sha256', $matches[1]))
        ->and($member->fresh()->delivery_status)->toBe('sent')->and($member->fresh()->sent_at)->not->toBeNull();
    expect(json_encode($member->fresh()->getAttributes()))->not->toContain($matches[1])->not->toContain('628123456789');
    (new SendActivityAccessLink($member->id, 0))->handle(app(MessagingService::class));
    Http::assertSentCount(1);
});

test('failed delivery exposes only failure status and cannot overwrite newer access', function () {
    Http::preventStrayRequests();
    Http::fake(['https://api.fonnte.com/send' => Http::response(['status' => false], 500)]);
    MessagingSetting::create(['channel' => 'whatsapp', 'enabled' => true, 'options' => ['token' => 'test-token']]);
    $member = ActivityRegistration::factory()->create(['channel' => 'whatsapp', 'destination' => '628123456789']);
    (new SendActivityAccessLink($member->id, 1))->handle(app(MessagingService::class));
    expect($member->fresh()->delivery_status)->toBe('failed');
    Http::assertSentCount(1);
});

test('admin participant filters edits validation and deactivation preserve activity boundaries', function () {
    Queue::fake([SendActivityAccessLink::class]);
    $activity = Activity::factory()->publicRegistration()->create();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $this->post(route('admin.activity-participants.store', $activity), activityEntryPayload())->assertSessionHasNoErrors();
    $entry = $activity->entries()->firstOrFail();
    $foreign = ActivityEntry::factory()->create(['name' => 'Peserta Kegiatan Lain']);
    $component = Livewire::test(GlobalParticipants::class, ['activityId' => $activity->id])->assertSee('Anggota1')->assertDontSee($foreign->name)
        ->set('search', 'tidak ditemukan')->assertDontSee('Anggota1')->set('search', 'Sekolah Asal')->assertSee('Anggota1')
        ->set('validation', 'validated')->assertDontSee('Anggota1')->call('validateEntry', $entry->id)->assertSee('Anggota1');
    $data = activityEntryPayload();
    $data['members'][0]['name'] = 'Nama Koreksi';
    $this->post(route('admin.activity-participants.update', [$activity->id, $entry->id]), $data)->assertSessionHasNoErrors();
    expect($entry->fresh()->validation_status)->toBe('pending')->and($entry->fresh()->name)->toBe('Nama Koreksi');
    $component->set('validation', '')->call('setActive', $entry->id, false)->assertHasNoErrors();
    expect($entry->fresh()->status)->toBe('inactive');
    $this->get(route('admin.activity-participants.edit', [$activity->id, $foreign->id]))->assertNotFound();
    $member = $entry->members()->where('status', 'active')->firstOrFail();
    expect(fn () => app(ActivityRegistrationService::class)->resend($member))->toThrow(HttpException::class);
});

test('global content banners and attachments can be saved downloaded and hidden with publication', function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $component = Livewire::test(PublicContent::class)->set('title', 'Agenda berlampiran')->set('body', 'Panduan peserta')
        ->set('startsAt', now()->toDateTimeString())->set('endsAt', now()->addDay()->toDateTimeString())->set('status', 'published')
        ->set('attachmentUploads', [UploadedFile::fake()->create('panduan.pdf', 20, 'application/pdf')])
        ->set('bannerUpload', UploadedFile::fake()->image('banner.jpg'))->call('save')->assertHasNoErrors();
    $activity = Activity::withoutGlobalScope('school')->where('title', 'Agenda berlampiran')->firstOrFail();
    Storage::disk('public')->assertExists($activity->banner_path);
    Storage::disk('local')->assertExists($activity->attachments[0]['path']);
    $this->get(route('public.activities.show', $activity))->assertSee('Banner Agenda berlampiran')->assertSee('panduan.pdf');
    $this->get(route('public.content.attachment', ['activities', $activity->id, 0]).'?download=1')->assertDownload('panduan.pdf');
    $component->call('edit', $activity->id)->assertSee('Hapus: panduan.pdf')->set('status', 'draft')->call('save')->assertHasNoErrors();
    $this->get(route('public.content.attachment', ['activities', $activity->id, 0]))->assertNotFound();
});

test('dynamic answers validate option membership dates times and required text', function (string $type, mixed $answer, bool $valid) {
    Queue::fake([SendActivityAccessLink::class]);
    $activity = Activity::factory()->publicRegistration()->create(['registration_fields' => [
        ['id' => 'question', 'label' => 'Pertanyaan kegiatan', 'description' => 'Petunjuk', 'type' => $type, 'required' => true, 'options' => ['A', 'B']],
    ]]);
    $data = activityEntryPayload();
    $data['answers'] = ['question' => $answer];
    $response = $this->post(route('public.activities.register.store', $activity), $data);
    if ($valid) {
        $response->assertSessionHasNoErrors();
        expect($activity->entries()->firstOrFail()->answers)->toBe(['question' => $answer]);
    } else {
        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('activity_entries', 0);
    }
})->with([
    'short valid' => ['short_text', 'Jawaban', true], 'short empty' => ['short_text', '', false],
    'paragraph valid' => ['paragraph', "Baris satu\nBaris dua", true], 'paragraph invalid' => ['paragraph', ['nested'], false],
    'radio valid' => ['radio', 'A', true], 'radio invalid' => ['radio', 'C', false],
    'checkbox valid' => ['checkbox', ['A', 'B'], true], 'checkbox invalid' => ['checkbox', ['C'], false], 'checkbox duplicate' => ['checkbox', ['A', 'A'], false],
    'select valid' => ['select', 'B', true], 'select invalid' => ['select', 'C', false],
    'date valid' => ['date', '2026-09-14', true], 'date invalid' => ['date', '2026-02-30', false],
    'time valid' => ['time', '14:30', true], 'time invalid' => ['time', '25:00', false],
]);

test('participant list ordering filters and public output escape names and reject injected order clauses', function () {
    $activity = Activity::factory()->publicRegistration()->create();
    ActivityEntry::factory()->create(['activity_id' => $activity->id, 'name' => 'Zulu', 'category' => 'team']);
    $entry = ActivityEntry::factory()->create(['activity_id' => $activity->id, 'name' => 'Alpha <script>alert(1)</script>', 'validation_status' => 'validated']);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    Livewire::test(GlobalParticipants::class, ['activityId' => $activity->id])->set('order', 'name')->assertSeeInOrder(['Alpha', 'Zulu'])
        ->set('order', 'name_desc')->assertSeeInOrder(['Zulu', 'Alpha'])->set('category', 'team')->assertDontSee('Alpha')
        ->set('category', '')->set('order', 'name; DROP TABLE activity_entries')->assertSee('Alpha')->assertHasNoErrors();
    $this->get(route('public.activities.participants', $activity))->assertSee($entry->name)->assertDontSee('<script>alert(1)</script>', false);
    $this->assertDatabaseCount('activity_entries', 2);
});

test('coach portal shows only its own entry and logout removes temporary access', function () {
    $coach = ActivityRegistration::factory()->create(['role' => 'coach', 'token_hash' => hash('sha256', $token = str_repeat('c', 64))]);
    ActivityRegistration::factory()->create(['entry_id' => $coach->entry_id, 'name' => 'Anggota dampingan']);
    ActivityRegistration::factory()->create(['name' => 'Anggota kegiatan lain']);
    $this->post(route('activity-access.enter', $token))->assertRedirect();
    $this->get(route('activity-access.portal', $coach))->assertOk()->assertSee('Menu pembina pendamping')->assertSee('Anggota dampingan')->assertDontSee('Anggota kegiatan lain');
    $this->post(route('activity-access.leave'))->assertRedirect(route('home'));
    $this->get(route('activity-access.portal', $coach))->assertForbidden();
});

test('assessment imports only validated active entries of the selected activity and mode', function () {
    $activity = Activity::factory()->publicRegistration()->create();
    ActivityEntry::factory()->create(['activity_id' => $activity->id, 'name' => 'Regu Terverifikasi', 'category' => 'penggalang', 'validation_status' => 'validated']);
    ActivityEntry::factory()->create(['activity_id' => $activity->id, 'name' => 'Belum Valid', 'category' => 'penggalang']);
    ActivityEntry::factory()->create(['name' => 'Kegiatan Lain', 'category' => 'penggalang', 'validation_status' => 'validated']);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    Livewire::test(GlobalManage::class, ['activityId' => $activity->id])
        ->call('useRegisteredParticipants')->assertSet('participants', 'Regu Terverifikasi')
        ->set('mode', 'individual')->call('useRegisteredParticipants')->assertHasErrors('participants');
});

test('announcement attachments use the same protected publication boundary for school and global information', function (bool $tenant) {
    Storage::fake('local');
    $announcement = new Announcement;
    $announcement->forceFill(['school_id' => $tenant ? School::factory()->create()->id : null,
        'created_by' => User::factory()->create()->id, 'title' => 'Pengumuman kegiatan', 'body' => 'Panduan kegiatan',
        'status' => 'published', 'is_public' => true, 'published_at' => now()->subMinute()])->save();
    app(ContentMediaService::class)->save($announcement, [UploadedFile::fake()->create('pengumuman.pdf', 10, 'application/pdf')]);
    Storage::disk('local')->assertExists($announcement->fresh()->attachments[0]['path']);
    $this->get(route('public.content.attachment', ['announcements', $announcement->id, 0]).'?download=1')->assertDownload('pengumuman.pdf');
    $announcement->update(['expires_at' => now()->subMinute()]);
    $this->get(route('public.content.attachment', ['announcements', $announcement->id, 0]))->assertNotFound();
})->with([true, false]);
