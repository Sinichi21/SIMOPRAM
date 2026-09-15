<?php

use App\Livewire\Activities\GlobalAttendance;
use App\Models\Activity;
use App\Models\ActivityDelegate;
use App\Models\ActivityEntry;
use App\Models\ActivityRegistration;
use App\Models\AttendanceSession;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\GlobalActivityAttendance;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('managers record and correct each attendance status on validated activity participants', function (string $status) {
    $this->freezeTime();
    $user = User::factory()->create(['system_role' => 'super_admin']);
    $entry = ActivityEntry::factory()->create(['validation_status' => 'validated']);
    $member = ActivityRegistration::factory()->create(['entry_id' => $entry->id]);
    Livewire::actingAs($user)->test(GlobalAttendance::class, ['activityId' => $entry->activity_id])->call('mark', $member->id, $status)->assertHasNoErrors();
    expect($member->fresh()->attendance_status)->toBe($status)->and($member->fresh()->attendance_marked_by)->toBe($user->id);
    expect($member->fresh()->checked_in_at !== null)->toBe($status === 'present');
    Livewire::test(GlobalAttendance::class, ['activityId' => $entry->activity_id])->call('mark', $member->id, 'unmarked')->assertHasNoErrors();
    expect($member->fresh()->attendance_status)->toBeNull()->and($member->fresh()->checked_in_at)->toBeNull();
})->with(['present', 'excused', 'sick', 'absent']);

test('attendance excludes pending inactive and removed participants', function (string $case) {
    $entry = ActivityEntry::factory()->create(['validation_status' => 'validated']);
    $member = ActivityRegistration::factory()->create(['entry_id' => $entry->id]);
    match ($case) {
        'pending' => $entry->update(['validation_status' => 'pending']),
        'inactive' => $entry->update(['status' => 'inactive']),
        'removed' => $member->update(['status' => 'revoked']),
    };
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    Livewire::test(GlobalAttendance::class, ['activityId' => $entry->activity_id])->assertDontSee($member->name);
    expect(fn () => app(GlobalActivityAttendance::class)->mark($entry->activity_id, $member->id, 'present'))->toThrow(ModelNotFoundException::class);
    expect($member->fresh()->attendance_status)->toBeNull();
})->with(['pending', 'inactive', 'removed']);

test('attendance cannot be changed or printed without activity specific access', function () {
    $entry = ActivityEntry::factory()->create(['validation_status' => 'validated']);
    $member = ActivityRegistration::factory()->create(['entry_id' => $entry->id]);
    $other = Activity::factory()->publicRegistration()->create();
    $this->get(route('admin.activity-attendance', $entry->activity_id))->assertRedirect(route('login'));
    $this->get(route('admin.activity-attendance.print', $entry->activity_id))->assertRedirect(route('login'));
    $user = User::factory()->create();
    ActivityDelegate::factory()->create(['activity_id' => $other->id, 'user_id' => $user->id]);
    $this->actingAs($user)->get(route('admin.activity-attendance', $entry->activity_id))->assertForbidden();
    $this->get(route('admin.activity-attendance.print', $entry->activity_id))->assertForbidden();
    Livewire::test(GlobalAttendance::class, ['activityId' => $other->id])->assertDontSee($member->name);
    expect(fn () => app(GlobalActivityAttendance::class)->mark($other->id, $member->id, 'present'))->toThrow(ModelNotFoundException::class);
});

test('attendance search role status and invalid status are handled without leaking contacts', function () {
    $entry = ActivityEntry::factory()->create(['validation_status' => 'validated', 'name' => 'Regu Garuda']);
    $member = ActivityRegistration::factory()->create(['entry_id' => $entry->id, 'name' => 'Andi Peserta', 'destination' => 'rahasia@example.com']);
    $coach = ActivityRegistration::factory()->create(['entry_id' => $entry->id, 'role' => 'coach', 'name' => 'Pembina Budi', 'attendance_status' => 'excused']);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    Livewire::test(GlobalAttendance::class, ['activityId' => $entry->activity_id])->assertSee('Andi Peserta')->assertDontSee('rahasia@example.com')
        ->set('search', 'Regu Garuda')->assertSee('Pembina Budi')->set('role', 'coach')->assertDontSee('Andi Peserta')
        ->set('role', '')->set('attendance', 'unmarked')->assertSee('Andi Peserta')->assertDontSee('Pembina Budi')
        ->call('mark', $member->id, 'not-a-status')->assertHasErrors('attendance');
});

test('participant check in shares attendance state but cannot override a manager correction', function () {
    $entry = ActivityEntry::factory()->create(['validation_status' => 'validated']);
    $member = ActivityRegistration::factory()->create(['entry_id' => $entry->id, 'token_hash' => hash('sha256', $token = str_repeat('d', 64))]);
    $this->post(route('activity-access.enter', $token))->assertRedirect();
    $this->post(route('activity-access.check-in', $member))->assertRedirect();
    expect($member->fresh()->attendance_status)->toBe('present');
    $member->forceFill(['attendance_status' => 'sick', 'checked_in_at' => null])->save();
    $this->post(route('activity-access.check-in', $member))->assertForbidden();
    $this->get(route('activity-access.portal', $member))->assertOk()->assertSee('Sakit')->assertDontSee('Konfirmasi kehadiran saya');
    expect($member->fresh()->attendance_status)->toBe('sick');
});

test('global attendance form and recap download as private pdf and filters select the roster', function () {
    $entry = ActivityEntry::factory()->create(['validation_status' => 'validated']);
    ActivityRegistration::factory()->create(['entry_id' => $entry->id, 'name' => 'Siswa Cetak']);
    ActivityRegistration::factory()->create(['entry_id' => $entry->id, 'role' => 'coach', 'name' => 'Pembina Cetak']);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $preview = $this->get(route('admin.activity-attendance.print', [$entry->activity_id, 'blank' => 1]))->assertOk();
    expect($preview->headers->get('Content-Disposition'))->toStartWith('inline;');
    $this->get(route('admin.activity-attendance.print', [$entry->activity_id, 'blank' => 1, 'role' => 'coach', 'download' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertDownload('form-absensi-'.$entry->activity_id.'.pdf');
    $this->get(route('admin.activity-attendance.print', [$entry->activity_id, 'blank' => 0, 'download' => 1]))->assertOk()->assertDownload('rekap-absensi-'.$entry->activity_id.'.pdf');
    expect(app(GlobalActivityAttendance::class)->participants($entry->activity, '', 'coach')->pluck('name')->all())->toBe(['Pembina Cetak']);
    $this->get(route('admin.activity-attendance.print', [$entry->activity_id, 'role' => 'invalid']))->assertSessionHasErrors('role');
});

test('blank attendance form omits recorded status and recap includes it with escaped identity', function () {
    $activity = Activity::factory()->publicRegistration()->create();
    $rows = collect([['name' => '<script>Peserta</script>', 'identifier' => 'NTA123', 'group' => 'Barung Melati', 'school' => 'Sekolah', 'role' => 'Peserta', 'status' => 'Sakit', 'time' => null]]);
    $blank = view('reports.pdf.activity-attendance-form', ['activity' => $activity, 'rows' => $rows, 'blank' => true, 'sessionName' => 'Sesi'])->render();
    expect($blank)->toContain('Tanda tangan')->not->toContain('Sakit')->not->toContain('<script>Peserta</script>');
    $recap = view('reports.pdf.activity-attendance-form', ['activity' => $activity, 'rows' => $rows, 'blank' => false, 'sessionName' => 'Sesi'])->render();
    expect($recap)->toContain('Sakit')->toContain('REKAP ABSENSI');
});

test('school attendance printing uses the selected session and rejects sessions of another activity or school', function () {
    $user = User::factory()->create(['system_role' => 'super_admin']);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $activity = Activity::factory()->create(['school_id' => $school->id]);
    $session = AttendanceSession::create(['activity_id' => $activity->id, 'created_by' => $user->id, 'name' => 'Pembukaan', 'open_at' => now(), 'close_at' => now()->addHour()]);
    $student = Student::factory()->create(['school_id' => $school->id]);
    $session->participants()->create(['student_id' => $student->id]);
    $otherActivity = Activity::factory()->create(['school_id' => $school->id]);
    $this->actingAs($user)->withSession(['active_school_id' => $school->id])->get(route('attendances.print', [$activity->id, $session->id]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get(route('attendances.print', [$otherActivity->id, $session->id]))->assertNotFound();
    $this->withSession(['active_school_id' => School::factory()->create()->id])->get(route('attendances.print', [$activity->id, $session->id]))->assertNotFound();
});
