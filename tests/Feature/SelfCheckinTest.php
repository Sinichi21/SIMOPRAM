<?php

use App\Livewire\Attendances\SelfCheckin;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolContext;
use Livewire\Livewire;

test('self attendance explains when the account has no active student in the selected school', function (string $profile) {
    $school = School::factory()->create();
    $user = User::factory()->create(['system_role' => 'super_admin']);
    if ($profile !== 'missing') {
        Student::factory()->create([
            'school_id' => $profile === 'other-school' ? School::factory()->create()->id : $school->id,
            'user_id' => $user->id,
            'status' => $profile === 'inactive' ? 'inactive' : 'active',
            'name' => 'Profil siswa tersembunyi',
        ]);
    }

    $this->actingAs($user)->withSession(['active_school_id' => $school->id])
        ->get(route('attendances.self'))
        ->assertOk()
        ->assertSee('Akun belum terhubung ke siswa aktif')
        ->assertDontSee('Profil siswa tersembunyi')
        ->assertDontSee('Absen Sekarang');
})->with(['missing', 'inactive', 'other-school']);

test('an active student can open self attendance without available sessions', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['system_role' => 'super_admin']);
    Student::factory()->create(['school_id' => $school->id, 'user_id' => $user->id, 'name' => 'Siswa Aktif']);

    $this->actingAs($user)->withSession(['active_school_id' => $school->id])
        ->get(route('attendances.self'))
        ->assertOk()
        ->assertSee('Siswa Aktif')
        ->assertDontSee('Akun belum terhubung ke siswa aktif');
});

test('an account without a student cannot submit a self checkin', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create(['system_role' => 'super_admin']);

    Livewire::actingAs($user)->test(SelfCheckin::class)
        ->call('checkIn', 1, -8.5, 115.2, 10)
        ->assertForbidden();

    $this->assertDatabaseCount('attendances', 0);
});
