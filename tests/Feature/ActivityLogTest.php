<?php

use App\Livewire\SchoolRegistrations\Index;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\AssessmentConfig;
use App\Models\AssessmentFactor;
use App\Models\Journal;
use App\Models\JournalAttachment;
use App\Models\MessagingSetting;
use App\Models\School;
use App\Models\SchoolRegistrationRequest;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\MessagingService;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('student edits record the actor school target and before and after values', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $student = Student::factory()->create(['school_id' => $school->id, 'name' => 'Nama Lama']);
    $user = User::factory()->create(['name' => 'Pembina A', 'system_role' => 'coach']);
    $this->actingAs($user);

    $student->update(['name' => 'Nama Baru']);

    $log = ActivityLog::where('target_type', 'Student')->where('target_id', $student->id)->where('action', 'updated')->sole();
    expect($log->user_id)->toBe($user->id)->and($log->user_name)->toBe('Pembina A')
        ->and($log->role)->toBe('coach')->and($log->school_id)->toBe($school->id)
        ->and($log->module)->toBe('students')->and($log->old_values)->toBe(['name' => 'Nama Lama'])
        ->and($log->new_values)->toBe(['name' => 'Nama Baru'])->and($log->request_id)->toStartWith('req_');
    $user->update(['name' => 'Nama Setelahnya']);
    expect($log->fresh()->user_name)->toBe('Pembina A');
});

test('grade changes retain actual scores and a readable student description', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $year = AcademicYear::factory()->create(['school_id' => $school->id]);
    $semester = Semester::create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'semester_number' => 1, 'start_date' => '2026-07-01', 'end_date' => '2026-12-31']);
    $config = AssessmentConfig::create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'name' => 'Nilai']);
    $student = Student::factory()->create(['school_id' => $school->id, 'name' => 'Siswa B']);
    $factor = AssessmentFactor::factory()->create(['school_id' => $school->id]);
    $score = StudentScore::create(['assessment_config_id' => $config->id, 'assessment_factor_id' => $factor->id, 'student_id' => $student->id, 'score' => 78, 'source' => 'manual']);
    $this->actingAs(User::factory()->create(['name' => 'Pembina A', 'system_role' => 'coach']));

    $score->update(['score' => 88]);

    $log = ActivityLog::where('target_type', 'StudentScore')->where('action', 'updated')->sole();
    expect((float) $log->old_values['score'])->toBe(78.0)->and((float) $log->new_values['score'])->toBe(88.0)
        ->and($log->summary())->toContain('Pembina A', 'siswa b', 'dari 78 menjadi 88', 'WITA');
});

test('journal deletion and restoration are recorded without journal contents', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['school_id' => $school->id]);
    $journal = Journal::create(['activity_id' => $activity->id, 'created_by' => $user->id, 'material' => 'ISI DOKUMEN RAHASIA', 'activity_description' => 'ISI DOKUMEN RAHASIA', 'status' => 'draft']);
    $this->actingAs($user);

    $journal->delete();
    $journal->restore();

    $logs = ActivityLog::where('target_type', 'Journal')->where('target_id', $journal->id)->get();
    expect($logs->pluck('action')->all())->toBe(['created', 'deleted', 'restored'])
        ->and($logs->toJson())->not->toContain('ISI DOKUMEN RAHASIA');
});

test('SMTP and password changes never log credentials or credential hashes', function () {
    $user = User::factory()->create(['system_role' => 'super_admin']);
    $this->actingAs($user);
    $setting = MessagingSetting::factory()->create(['channel' => 'email', 'options' => ['password' => 'OLD-SMTP-SECRET', 'token' => 'BOT-SECRET']]);

    $setting->update(['options' => ['password' => 'NEW-SMTP-SECRET', 'host' => 'smtp.example.com']]);
    $hash = Hash::make('NEW-USER-SECRET');
    $user->update(['password' => $hash]);

    $log = ActivityLog::where('action', 'password_changed')->sole();
    expect($log->old_values)->toBeNull()->and($log->new_values)->toBeNull()->and($log->log_type)->toBe('security');
    $smtp = ActivityLog::where('target_type', 'MessagingSetting')->where('action', 'updated')->sole();
    expect($smtp->user_id)->toBe($user->id)->and($smtp->new_values['options'])->toBe('[DISEMBUNYIKAN]');
    expect(ActivityLog::all()->toJson())->not->toContain('OLD-SMTP-SECRET', 'NEW-SMTP-SECRET', 'BOT-SECRET', 'NEW-USER-SECRET', $hash);
});

test('the logger redacts unknown nested fields and normalizes the user agent', function () {
    request()->headers->set('User-Agent', 'Mozilla Windows Chrome/123 COOKIE-SECRET');
    $values = ['score' => 88, 'password' => 'PASS', 'api_key' => 'KEY', 'session' => 'SESSION', 'private_key' => 'PRIVATE', 'body' => 'DOCUMENT', 'options' => ['token' => 'TOKEN'], 'grade' => ['cookie' => 'COOKIE']];

    $log = app(ActivityLogger::class)->record('grades', 'updated', old: $values, new: $values);

    expect($log->new_values['score'])->toBe(88)->and($log->new_values['grade']['cookie'])->toBe('[DISEMBUNYIKAN]')
        ->and($log->user_agent)->toBe('Chrome / Windows');
    expect($log->toJson())->not->toContain('"PASS"', '"KEY"', '"SESSION"', '"PRIVATE"', '"DOCUMENT"', '"TOKEN"', 'COOKIE-SECRET');
});

test('rolled back changes do not leave successful audit rows', function () {
    $school = School::factory()->create(['name' => 'Nama Awal']);
    DB::beginTransaction();
    $school->update(['name' => 'Nama Batal']);
    DB::rollBack();

    $this->assertDatabaseMissing('activity_logs', ['target_type' => 'School', 'target_id' => $school->id, 'action' => 'updated']);
    expect($school->fresh()->name)->toBe('Nama Awal');
});

test('authentication events record successes failures and logout without supplied credentials', function () {
    $user = User::factory()->create();

    Auth::attempt(['email' => $user->email, 'password' => 'WRONG-SECRET']);
    Auth::attempt(['email' => $user->email, 'password' => 'password']);
    Auth::logout();

    expect(ActivityLog::where('log_type', 'security')->where('action', 'login')->pluck('status')->all())->toBe(['failed', 'success']);
    $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'logout', 'status' => 'success']);
    expect(ActivityLog::all()->toJson())->not->toContain('WRONG-SECRET');
});

test('school decisions use standardized approved and rejected actions', function () {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $registration = SchoolRegistrationRequest::factory()->create();

    Livewire::actingAs($admin)->test(Index::class)->call('show', $registration->id)->call('approve')->assertHasNoErrors();

    $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'module' => 'schools', 'target_type' => 'SchoolRegistrationRequest', 'target_id' => $registration->id, 'action' => 'approved']);
});

test('role assignments emit a security audit', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    setPermissionsTeamId($school->id);
    $user = User::factory()->create();
    $role = Role::create(['name' => 'coach', 'guard_name' => 'web']);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));

    $user->assignRole($role);

    $log = ActivityLog::where('log_type', 'security')->where('module', 'roles')->sole();
    expect($log->new_values)->toBe(['role_ids' => [$role->id]])->and($log->target_id)->toBe((string) $user->id);
});

test('system failures record safe metadata and request IDs without exception messages', function () {
    Route::get('/audit-test-failure', fn () => throw new RuntimeException('SMTP-PASSWORD-MUST-NOT-LEAK'));

    $response = $this->get('/audit-test-failure')->assertStatus(500);

    $log = ActivityLog::where('log_type', 'system')->where('status', 'failed')->sole();
    expect($log->request_id)->toBe($response->headers->get('X-Request-ID'))
        ->and($log->toJson())->not->toContain('SMTP-PASSWORD-MUST-NOT-LEAK');
});

test('audit records cannot be edited or deleted through the model', function () {
    $log = ActivityLog::factory()->create();

    expect(fn () => $log->update(['description' => 'Diubah']))->toThrow(LogicException::class);
    expect(fn () => $log->delete())->toThrow(LogicException::class);
    $this->assertModelExists($log);
});

test('Livewire downloads create audit entries after the file is generated', function () {
    $user = User::factory()->create(['system_role' => 'super_admin']);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);

    Livewire::actingAs($user)->test(App\Livewire\Students\Index::class)
        ->call('downloadCsvTemplate')->assertFileDownloaded();

    $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'module' => 'students', 'action' => 'downloaded', 'status' => 'success']);
});

test('message delivery logs provider acceptance and failure without message bodies or tokens', function () {
    Http::preventStrayRequests();
    Http::fake(['api.fonnte.com/*' => Http::sequence()->push(['status' => true])->push(['status' => false])]);
    MessagingSetting::factory()->create(['channel' => 'whatsapp', 'enabled' => true, 'options' => ['token' => 'PRIVATE-API-KEY']]);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $service = app(MessagingService::class);

    $service->send('whatsapp', '081234567890', 'PRIVATE-MESSAGE');
    expect(fn () => $service->send('whatsapp', '081234567890', 'PRIVATE-MESSAGE'))->toThrow(RuntimeException::class);

    expect(ActivityLog::where('module', 'messaging')->pluck('action')->all())->toBe(['sent', 'failed']);
    expect(ActivityLog::all()->toJson())->not->toContain('PRIVATE-MESSAGE', 'PRIVATE-API-KEY');
});

test('uploaded journal attachment logs metadata without file contents or storage paths', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['school_id' => $school->id]);
    $journal = Journal::create(['activity_id' => $activity->id, 'created_by' => $user->id, 'material' => 'Materi', 'activity_description' => 'Kegiatan', 'status' => 'draft']);
    $this->actingAs($user);

    $attachment = JournalAttachment::create(['journal_id' => $journal->id, 'uploaded_by' => $user->id, 'original_name' => 'secret-document.pdf', 'path' => 'private/secret-document.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 123]);

    $log = ActivityLog::where('action', 'uploaded')->sole();
    expect($log->target_id)->toBe((string) $attachment->id)->and($log->new_values['size_bytes'])->toBe(123)
        ->and($log->toJson())->not->toContain('secret-document.pdf');
});
