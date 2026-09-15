<?php

use App\Models\Complaint;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** @return array<string, string> */
function complaintPayload(): array
{
    return ['name' => 'Pelapor Uji', 'email' => 'pelapor@example.com', 'category' => 'Layanan aplikasi', 'subject' => 'Presensi tidak tersimpan', 'body' => 'Presensi kegiatan hari ini tidak dapat disimpan setelah dikirim.'];
}

function complaintMember(School $school, string $role = 'student'): User
{
    $user = User::factory()->create(['system_role' => $role]);
    $user->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true, 'joined_at' => today()]);

    return $user;
}

test('public visitors can open the complaint form', function () {
    $this->get(route('complaints.public'))->assertOk()->assertSee('Sampaikan pengaduan Anda.');
});

test('public submissions store private attachments and cannot assign a school or status', function () {
    Storage::fake('local');

    $response = $this->post(route('complaints.public.store'), [
        ...complaintPayload(), 'school_id' => School::factory()->create()->id, 'status' => 'resolved',
        'attachment' => UploadedFile::fake()->create('bukti.pdf', 20, 'application/pdf'),
    ]);

    $response->assertRedirect(route('complaints.public'))->assertSessionHas('complaint-reference');
    $record = Complaint::query()->sole();
    expect($record->school_id)->toBeNull()->and($record->user_id)->toBeNull()->and($record->status)->toBe('new');
    Storage::disk('local')->assertExists($record->attachment_path);
});

test('invalid complaints are rejected', function (array $changes, string $field) {
    $this->post(route('complaints.public.store'), [...complaintPayload(), ...$changes])
        ->assertSessionHasErrors($field);
    $this->assertDatabaseCount('complaints', 0);
})->with([
    [['email' => 'invalid'], 'email'],
    [['body' => 'short'], 'body'],
    [['category' => 'invalid'], 'category'],
    [['subject' => ''], 'subject'],
]);

test('unsafe attachments are rejected', function () {
    $this->post(route('complaints.public.store'), [
        ...complaintPayload(), 'attachment' => UploadedFile::fake()->create('code.html', 2, 'text/html'),
    ])->assertSessionHasErrors('attachment');
    $this->assertDatabaseCount('complaints', 0);
});

test('tracking requires the correct public reference and email and escapes content', function () {
    $record = Complaint::factory()->create(['body' => '<script>alert(1)</script>', 'response' => 'Pengaduan sudah diperiksa.', 'status' => 'resolved']);

    $this->post(route('complaints.track'), ['reference' => $record->reference, 'tracking_email' => $record->email])
        ->assertOk()->assertSee('Pengaduan sudah diperiksa.')->assertSee('Selesai')
        ->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
});

test('tracking rejects incorrect credentials and school complaints', function (bool $internal) {
    $record = Complaint::factory()->create(['school_id' => $internal ? School::factory()->create()->id : null]);

    $this->post(route('complaints.track'), [
        'reference' => $record->reference, 'tracking_email' => $internal ? $record->email : 'wrong@example.com',
    ])->assertSessionHasErrors('reference');
})->with([false, true]);

test('school submissions use the current account and school', function () {
    $school = School::factory()->create();
    $user = complaintMember($school);

    $this->actingAs($user)->withSession(['active_school_id' => $school->id])
        ->post(route('complaints.store'), [...complaintPayload(), 'school_id' => 999, 'user_id' => 999])
        ->assertRedirect();

    $this->assertDatabaseHas('complaints', ['user_id' => $user->id, 'school_id' => $school->id, 'name' => $user->name, 'email' => $user->email]);
});

test('users see only their own school complaints', function () {
    $school = School::factory()->create();
    $user = complaintMember($school);
    $own = Complaint::factory()->create(['user_id' => $user->id, 'school_id' => $school->id, 'subject' => 'Pengaduan milik sendiri']);
    Complaint::factory()->create(['school_id' => $school->id, 'subject' => 'Pengaduan milik orang lain']);

    $this->actingAs($user)->withSession(['active_school_id' => $school->id])
        ->get(route('complaints.index'))->assertOk()->assertSee($own->subject)->assertDontSee('Pengaduan milik orang lain');
});

test('users cannot view download or update another complaint', function (string $action) {
    $school = School::factory()->create();
    $user = complaintMember($school);
    $record = Complaint::factory()->create(['school_id' => $school->id]);
    $this->actingAs($user)->withSession(['active_school_id' => $school->id]);

    if ($action === 'update') {
        $this->patch(route('complaints.update', $record->id), ['status' => 'resolved', 'response' => 'Unauthorized response'])->assertNotFound();
    } else {
        $this->get(route('complaints.'.$action, $record->id))->assertNotFound();
    }
    expect($record->refresh()->status)->toBe('new');
})->with(['show', 'attachment', 'update']);

test('a reporter cannot respond to their own complaint', function () {
    $school = School::factory()->create();
    $user = complaintMember($school);
    $record = Complaint::factory()->create(['school_id' => $school->id, 'user_id' => $user->id]);

    $this->actingAs($user)->withSession(['active_school_id' => $school->id])
        ->patch(route('complaints.update', $record->id), ['status' => 'resolved', 'response' => 'Unauthorized response'])
        ->assertForbidden();
    expect($record->refresh()->status)->toBe('new');
});

test('school managers cannot access public complaints or another school', function (bool $public) {
    $school = School::factory()->create();
    $manager = complaintMember($school, 'scout_admin');
    $record = Complaint::factory()->create(['school_id' => $public ? null : School::factory()->create()->id]);

    $this->actingAs($manager)->withSession(['active_school_id' => $school->id])
        ->get(route('complaints.show', $record->id))->assertNotFound();
})->with([false, true]);

test('school managers can respond to their school complaints', function () {
    $school = School::factory()->create();
    $manager = complaintMember($school, 'scout_admin');
    $record = Complaint::factory()->create(['school_id' => $school->id]);

    $this->actingAs($manager)->withSession(['active_school_id' => $school->id])
        ->patch(route('complaints.update', $record->id), ['status' => 'processing', 'response' => 'Pengaduan sedang kami periksa.'])
        ->assertRedirect(route('complaints.show', $record->id));

    $this->assertDatabaseHas('complaints', ['id' => $record->id, 'status' => 'processing', 'responded_by' => $manager->id]);
});

test('super admins can handle public complaints and download evidence', function () {
    Storage::fake('local');
    Storage::disk('local')->put('complaints/evidence.pdf', 'evidence');
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $record = Complaint::factory()->create(['attachment_path' => 'complaints/evidence.pdf']);

    $this->actingAs($admin)->get(route('complaints.show', $record->id))->assertOk()->assertSee('Tindak lanjut pengaduan');
    $this->get(route('complaints.attachment', $record->id).'?download=1')->assertDownload('evidence.pdf');
    $this->patch(route('complaints.update', $record->id), ['status' => 'resolved', 'response' => 'Kendala telah kami perbaiki.'])->assertRedirect();
    $this->assertDatabaseHas('complaints', ['id' => $record->id, 'status' => 'resolved', 'response' => 'Kendala telah kami perbaiki.']);
});

test('response validation rejects unknown statuses and empty responses', function () {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $record = Complaint::factory()->create();

    $this->actingAs($admin)->patch(route('complaints.update', $record->id), ['status' => 'invalid', 'response' => ''])
        ->assertSessionHasErrors(['status', 'response']);
    expect($record->refresh()->status)->toBe('new');
});

test('public submissions are rate limited', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('complaints.public.store'), complaintPayload())->assertRedirect();
    }

    $this->post(route('complaints.public.store'), complaintPayload())->assertTooManyRequests();
    $this->assertDatabaseCount('complaints', 5);
});

test('school admin permissions allow handling only the selected school', function () {
    $this->seed(RolePermissionSeeder::class);
    $school = School::factory()->create();
    $manager = complaintMember($school, 'school_admin');
    setPermissionsTeamId($school->id);
    $manager->assignRole('school_admin');
    $record = Complaint::factory()->create(['school_id' => $school->id]);
    $other = Complaint::factory()->create(['school_id' => School::factory()->create()->id]);

    $this->actingAs($manager)->withSession(['active_school_id' => $school->id])
        ->get(route('complaints.show', $record->id))->assertOk()->assertSee('Tindak lanjut pengaduan');
    $this->get(route('complaints.show', $other->id))->assertNotFound();
});

test('users without a selected school cannot submit school complaints', function () {
    $user = User::factory()->create(['system_role' => 'super_admin']);

    $this->actingAs($user)->post(route('complaints.store'), complaintPayload())->assertStatus(409);
    $this->assertDatabaseCount('complaints', 0);
});

test('guests cannot open the complaint management pages', function () {
    $this->get(route('complaints.index'))->assertRedirect(route('login'));
});
