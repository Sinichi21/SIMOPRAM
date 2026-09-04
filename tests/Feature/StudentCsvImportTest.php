<?php

use App\Livewire\Students\Index;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\School;
use App\Models\ScoutLevel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('authorized user can import valid students and receives row errors', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create([
        'is_active' => true,
    ]);

    $this
        ->actingAs($user)
        ->withSession(['active_school_id' => $school->id])
        ->get(route('dashboard'))
        ->assertOk();

    $academicYear = AcademicYear::query()->create([
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);
    $classroom = Classroom::query()->create([
        'name' => 'Kelas 7A',
        'grade' => 7,
        'is_active' => true,
    ]);
    $scoutLevel = ScoutLevel::query()->create([
        'code' => 'PENGGALANG',
        'name' => 'Penggalang',
        'sort_order' => 2,
    ]);
    $csv = implode("\n", [
        'nis,nisn,nama,jenis_kelamin,tempat_lahir,tanggal_lahir,telepon,telepon_orang_tua,alamat,tanggal_masuk,status,tahun_ajaran,kelas,golongan',
        '2026001,0012345678,Budi Santoso,L,Makassar,2012-05-10,081234567890,081298765432,Jalan Pramuka,2026-07-01,active,2026/2027,Kelas 7A,Penggalang',
        '2026002,,Siti Aminah,P,,,,,,,active,2026/2027,Kelas Tidak Ada,Penggalang',
    ]);

    Livewire::test(Index::class)
        ->set('csvFile', UploadedFile::fake()->createWithContent('siswa.csv', $csv))
        ->call('importCsv')
        ->assertHasNoErrors()
        ->assertSet('importErrors.0', 'Baris 3: Tahun ajaran, kelas, atau golongan tidak ditemukan.')
        ->assertSee('Impor selesai. Berhasil: 1, gagal: 1.');

    $this->assertDatabaseHas('students', [
        'school_id' => $school->id,
        'nis' => '2026001',
        'name' => 'Budi Santoso',
        'gender' => 'L',
    ]);
    $this->assertDatabaseHas('student_enrollments', [
        'school_id' => $school->id,
        'academic_year_id' => $academicYear->id,
        'classroom_id' => $classroom->id,
    ]);
    $this->assertDatabaseHas('student_scout_levels', [
        'school_id' => $school->id,
        'academic_year_id' => $academicYear->id,
        'scout_level_id' => $scoutLevel->id,
    ]);
    $this->assertDatabaseMissing('students', [
        'nis' => '2026002',
    ]);
});

test('csv import rejects files without required columns', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();

    $this
        ->actingAs($user)
        ->withSession(['active_school_id' => $school->id])
        ->get(route('dashboard'))
        ->assertOk();

    Livewire::test(Index::class)
        ->set(
            'csvFile',
            UploadedFile::fake()->createWithContent('siswa.csv', "nis,nama\n1,Budi")
        )
        ->call('importCsv')
        ->assertHasErrors(['csvFile']);

    expect(Student::query()->count())->toBe(0);
});
