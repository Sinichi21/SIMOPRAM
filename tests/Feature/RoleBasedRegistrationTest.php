<?php

use App\Livewire\Auth\Register;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('renders the role based registration component', function () {
    Livewire::test(Register::class)
        ->assertSee('Daftar Akun SIMPRAM')
        ->assertSee('Siswa')
        ->assertSee('Pembina')
        ->assertSee('Admin Sekolah');
});

it('registers student only after matching an existing active unlinked student', function () {
    $school = School::factory()->create(['is_active' => true]);
    $student = Student::factory()->create([
        'school_id' => $school->id,
        'user_id' => null,
        'status' => 'active',
        'nis' => '2101',
        'nisn' => '3149600001',
        'name' => 'Siswa Terdaftar',
    ]);

    Livewire::test(Register::class)
        ->call('chooseRole', 'student')
        ->set('school_id', $school->id)
        ->set('student_identifier', '2101')
        ->call('findStudent')
        ->assertSet('matched_student_id', $student->id)
        ->assertSet('name', 'Siswa Terdaftar')
        ->set('email', 'siswa@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'siswa@example.com')->firstOrFail();
    $data = json_decode($user->getRawOriginal('registration_data'), true);

    expect($user->name)->toBe('Siswa Terdaftar')
        ->and($user->requested_role)->toBe('student')
        ->and($user->requested_school_id)->toBe($school->id)
        ->and($user->approval_status)->toBe('pending')
        ->and($user->is_active)->toBeFalse()
        ->and($data['student_id'])->toBe($student->id)
        ->and(Hash::check('Password123!', $user->password))->toBeTrue();
});

it('does not match students from another school', function () {
    $school = School::factory()->create(['is_active' => true]);
    $otherSchool = School::factory()->create(['is_active' => true]);

    Student::factory()->create([
        'school_id' => $otherSchool->id,
        'user_id' => null,
        'status' => 'active',
        'nis' => '9999',
    ]);

    Livewire::test(Register::class)
        ->call('chooseRole', 'student')
        ->set('school_id', $school->id)
        ->set('student_identifier', '9999')
        ->call('findStudent')
        ->assertSet('matched_student_id', null);
});

it('stores coach specific registration data', function () {
    $school = School::factory()->create(['is_active' => true]);

    Livewire::test(Register::class)
        ->call('chooseRole', 'coach')
        ->set('school_id', $school->id)
        ->set('name', 'Pembina Contoh')
        ->set('phone', '081234567890')
        ->set('nta', '091234')
        ->set('coach_position', 'Pembina Penggalang')
        ->set('email', 'coach-register@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'coach-register@example.com')->firstOrFail();
    $data = json_decode($user->getRawOriginal('registration_data'), true);

    expect($user->requested_role)->toBe('coach')
        ->and($data['nta'])->toBe('091234')
        ->and($data['position'])->toBe('Pembina Penggalang');
});

it('stores school admin request without granting system role immediately', function () {
    $school = School::factory()->create(['is_active' => true]);

    Livewire::test(Register::class)
        ->call('chooseRole', 'school_admin')
        ->set('school_id', $school->id)
        ->set('name', 'Admin Sekolah')
        ->set('phone', '081234567891')
        ->set('admin_position', 'Operator Sekolah')
        ->set('email', 'school-admin-register@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'school-admin-register@example.com')->firstOrFail();

    expect($user->requested_role)->toBe('school_admin')
        ->and($user->system_role)->not->toBe('school_admin')
        ->and($user->is_active)->toBeFalse();
});
