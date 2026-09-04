<?php

use App\Livewire\Students\Index;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\School;
use App\Models\ScoutLevel;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentScoutLevel;
use App\Models\User;
use Livewire\Livewire;

test('student list can be searched and filtered by student placement', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();

    $this->actingAs($user)
        ->withSession(['active_school_id' => $school->id])
        ->get(route('dashboard'))
        ->assertOk();

    $academicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'is_active' => true,
    ]);
    $otherAcademicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'is_active' => false,
    ]);
    $firstClassroom = Classroom::query()->create([
        'name' => 'Kelas Mawar',
        'grade' => 7,
        'is_active' => true,
    ]);
    $secondClassroom = Classroom::query()->create([
        'name' => 'Kelas Melati',
        'grade' => 8,
        'is_active' => true,
    ]);
    $firstScoutLevel = ScoutLevel::query()->create([
        'code' => 'PENGGALANG-FILTER',
        'name' => 'Penggalang Filter',
        'sort_order' => 1,
    ]);
    $secondScoutLevel = ScoutLevel::query()->create([
        'code' => 'PENEGAK-FILTER',
        'name' => 'Penegak Filter',
        'sort_order' => 2,
    ]);
    $andi = Student::query()->create([
        'nis' => 'S-001',
        'nisn' => 'N-001',
        'name' => 'Andi Terpilih',
        'gender' => 'L',
        'status' => 'active',
    ]);
    $siti = Student::query()->create([
        'nis' => 'S-002',
        'nisn' => 'N-002',
        'name' => 'Siti Tidak Terpilih',
        'gender' => 'P',
        'status' => 'inactive',
    ]);

    foreach ([
        [$andi, $academicYear, $firstClassroom, $firstScoutLevel],
        [$siti, $otherAcademicYear, $secondClassroom, $secondScoutLevel],
    ] as [$student, $year, $classroom, $scoutLevel]) {
        StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'status' => 'active',
        ]);
        StudentScoutLevel::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'scout_level_id' => $scoutLevel->id,
            'is_active' => true,
        ]);
    }

    Livewire::test(Index::class)
        ->assertSet('filterAcademicYearId', (string) $academicYear->id)
        ->assertSee('Andi Terpilih')
        ->assertDontSee('Siti Tidak Terpilih')
        ->set('filterAcademicYearId', '')
        ->set('search', 'Melati')
        ->assertSee('Siti Tidak Terpilih')
        ->assertDontSee('Andi Terpilih')
        ->set('search', '')
        ->set('filterStatus', 'active')
        ->set('filterGender', 'L')
        ->set('filterClassroomId', (string) $firstClassroom->id)
        ->set('filterScoutLevelId', (string) $firstScoutLevel->id)
        ->assertSee('Andi Terpilih')
        ->assertDontSee('Siti Tidak Terpilih');
});

test('resetting student filters restores the active academic year', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();

    $this->actingAs($user)
        ->withSession(['active_school_id' => $school->id])
        ->get(route('dashboard'))
        ->assertOk();

    $academicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->set('filterAcademicYearId', '')
        ->set('search', 'Andi')
        ->set('filterStatus', 'inactive')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('filterStatus', '')
        ->assertSet('filterAcademicYearId', (string) $academicYear->id);
});
