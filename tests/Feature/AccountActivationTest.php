<?php

use App\Livewire\CoachAccounts\Manage;
use App\Livewire\UserApprovals\Index;
use App\Models\Coach;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\Student;
use App\Models\User;
use App\Services\CoachAccountService;
use App\Support\SchoolContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->school = School::factory()->create();
    app(SchoolContext::class)->set($this->school);
    $this->admin = User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($this->admin)->withSession(['active_school_id' => $this->school->id]);
});

test('admin invites coach without providing a password and owner activates using emailed link', function () {
    Notification::fake();
    $coach = Coach::query()->create(['name' => 'Pembina Baru', 'is_active' => true]);
    Livewire::test(Manage::class, ['coachId' => $coach->id])
        ->assertDontSee('Konfirmasi Password')
        ->set('email', 'baru@example.com')
        ->call('createAccount')
        ->assertHasNoErrors()
        ->call('sendLink', 'email')
        ->assertHasNoErrors();
    $user = $coach->fresh()->user;
    expect($user->is_active)->toBeFalse()->and($user->activation_pending)->toBeTrue();

    auth()->logout();
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $url = $notification->toMail($user)->actionUrl;
        $this->get($url)->assertOk()->assertSee('value="'.$notification->token.'"', false);
        $payload = ['email' => $user->email, 'token' => $notification->token,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->post(route('password.update'), $payload)->assertSessionHasNoErrors();
        expect($user->fresh()->is_active)->toBeTrue()
            ->and($user->fresh()->activation_pending)->toBeFalse()
            ->and(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('email');

        return true;
    });
});

test('existing coach uses one account across schools without changing password or previous membership', function () {
    $first = Coach::query()->create(['name' => 'Pembina Bersama', 'is_active' => true]);
    $user = app(CoachAccountService::class)->createAccount($first, 'bersama@example.com');
    $user->update(['is_active' => true, 'activation_pending' => false]);
    $password = $user->password;
    $otherSchool = School::factory()->create();
    app(SchoolContext::class)->set($otherSchool);
    $second = Coach::query()->create(['name' => 'Pembina Bersama', 'is_active' => true]);
    $linked = app(CoachAccountService::class)->createAccount($second, ' BERSAMA@example.com ');
    expect($linked->id)->toBe($user->id)
        ->and($linked->password)->toBe($password)
        ->and($linked->is_active)->toBeTrue()
        ->and($linked->schoolMemberships()->count())->toBe(2);
    foreach ([$this->school->id, $otherSchool->id] as $schoolId) {
        $this->assertDatabaseHas('school_user_memberships', ['school_id' => $schoolId, 'user_id' => $user->id, 'is_active' => true]);
    }
    expect(User::query()->where('email', 'bersama@example.com')->count())->toBe(1);
});

test('share link includes token and email and issuance is throttled', function () {
    $coach = Coach::query()->create(['name' => 'Pembina Tautan', 'is_active' => true]);
    $user = app(CoachAccountService::class)->createAccount($coach, 'tautan@example.com');
    $component = Livewire::test(Manage::class, ['coachId' => $coach->id])->call('sendLink', 'share')->assertHasNoErrors();
    $url = $component->get('activationLink');
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    expect($query['email'])->toBe($user->email)
        ->and(Password::broker()->tokenExists($user, basename(parse_url($url, PHP_URL_PATH))))->toBeTrue();
    $component->assertSee('Bagikan ke WhatsApp')->assertSee('Bagikan ke Telegram')
        ->call('sendLink', 'share')->assertHasErrors('activation');
});

test('password reset does not activate a rejected or administratively disabled account', function (string $status) {
    $user = User::factory()->create(['is_active' => false, 'approval_status' => $status, 'activation_pending' => false]);
    $token = Password::broker()->createToken($user);
    auth()->logout();
    $this->post(route('password.update'), ['email' => $user->email, 'token' => $token,
        'password' => 'replacement-password', 'password_confirmation' => 'replacement-password'])->assertSessionHasNoErrors();
    expect($user->fresh()->is_active)->toBeFalse();
})->with(['approved', 'rejected', 'pending']);

test('admin cannot send reset links for users outside the active school', function () {
    $user = User::factory()->create(['system_role' => 'coach', 'approval_status' => 'approved', 'is_active' => true]);
    $otherSchool = School::factory()->create();
    SchoolUserMembership::query()->create(['school_id' => $otherSchool->id, 'user_id' => $user->id, 'is_active' => true]);
    Livewire::test(Index::class)->call('sendLink', $user->id, 'share')->assertNotFound();
    $this->assertDatabaseCount('password_reset_tokens', 0);
});

test('unprivileged user cannot manage accounts', function () {
    $user = User::factory()->create(['system_role' => 'student', 'is_active' => true]);
    Livewire::actingAs($user)->test(Index::class)->assertForbidden();
});

test('account linking refuses privileged users and duplicate local profiles', function () {
    $coach = Coach::query()->create(['name' => 'Pembina', 'is_active' => true]);
    Livewire::test(Manage::class, ['coachId' => $coach->id])
        ->set('email', $this->admin->email)->call('createAccount')->assertHasErrors('email');
    $user = app(CoachAccountService::class)->createAccount($coach, 'duplikat@example.com');
    $other = Coach::query()->create(['name' => 'Duplikat', 'is_active' => true]);
    Livewire::test(Manage::class, ['coachId' => $other->id])
        ->set('email', $user->email)->call('createAccount')->assertHasErrors('email');
    expect($other->fresh()->user_id)->toBeNull();
});

test('approved users are listed only in their school and sidebar has dedicated user management', function () {
    $coach = Coach::query()->create(['name' => 'Pembina Sekolah Ini', 'is_active' => true]);
    app(CoachAccountService::class)->createAccount($coach, 'lokal@example.com');
    User::factory()->create(['name' => 'User Tanpa Keanggotaan', 'approval_status' => 'approved']);
    Livewire::test(Index::class)->set('status', 'approved')->assertSee('lokal@example.com')->assertDontSee('User Tanpa Keanggotaan');
    $this->get(route('user-approvals.index'))->assertOk()->assertSee('Manajemen User')->assertSee('Akun dan Persetujuan');
});

test('student account directory and activation actions work without admin password fields', function () {
    $student = Student::factory()->create(['school_id' => $this->school->id, 'name' => 'Siswa Akun Lokal']);
    $this->get(route('student-accounts.index'))->assertOk()->assertSee('Siswa Akun Lokal');
    $otherSchool = School::factory()->create();
    Student::factory()->create(['school_id' => $otherSchool->id, 'name' => 'Siswa Akun Lain']);
    $this->get(route('student-accounts.index'))->assertDontSee('Siswa Akun Lain');
    Livewire::test(\App\Livewire\StudentAccounts\Manage::class, ['studentId' => $student->id])
        ->assertDontSee('Konfirmasi Password')
        ->set('email', 'siswa-aktivasi@example.com')->call('createAccount')->assertHasNoErrors()
        ->call('sendLink', 'share')->assertHasNoErrors()->assertSee('Bagikan ke WhatsApp');
    expect($student->fresh()->user->activation_pending)->toBeTrue();
});

test('approving registration keeps account inactive until activation link is used', function () {
    $user = User::factory()->create(['requested_school_id' => $this->school->id,
        'requested_role' => 'coach', 'approval_status' => 'pending', 'is_active' => false]);
    Livewire::test(Index::class)->call('approve', $user->id)->assertHasNoErrors()
        ->assertSee('Menunggu aktivasi')->call('sendLink', $user->id, 'share')->assertHasNoErrors();
    expect($user->fresh()->is_active)->toBeFalse()->and($user->fresh()->activation_pending)->toBeTrue();
});

test('expired activation token cannot activate account', function () {
    $user = User::factory()->create(['is_active' => false, 'approval_status' => 'approved', 'activation_pending' => true]);
    $token = Password::broker()->createToken($user);
    $this->travel(61)->minutes();
    auth()->logout();
    $this->post(route('password.update'), ['email' => $user->email, 'token' => $token,
        'password' => 'replacement-password', 'password_confirmation' => 'replacement-password'])->assertSessionHasErrors('email');
    expect($user->fresh()->is_active)->toBeFalse()->and($user->fresh()->activation_pending)->toBeTrue();
});

test('system accounts cannot receive reset links from school account management', function () {
    SchoolUserMembership::query()->create(['school_id' => $this->school->id, 'user_id' => $this->admin->id, 'is_active' => true]);
    Livewire::test(Index::class)->call('sendLink', $this->admin->id, 'share')->assertForbidden();
    $this->assertDatabaseCount('password_reset_tokens', 0);
});

test('failed email delivery leaves activation pending and allows sharing a replacement link', function () {
    $coach = Coach::query()->create(['name' => 'Pembina Email', 'is_active' => true]);
    $user = app(CoachAccountService::class)->createAccount($coach, 'gagal-kirim@example.com');
    Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Mail unavailable'));
    Livewire::test(Manage::class, ['coachId' => $coach->id])->call('sendLink', 'email')
        ->assertHasErrors('activation')->call('sendLink', 'share')->assertHasNoErrors();
    expect($user->fresh()->is_active)->toBeFalse()->and($user->fresh()->activation_pending)->toBeTrue();
});

test('account directory requires authentication', function () {
    auth()->logout();
    $this->get(route('coach-accounts.index'))->assertRedirect(route('login'));
});
