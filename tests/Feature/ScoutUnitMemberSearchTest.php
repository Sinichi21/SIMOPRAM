<?php

use App\Livewire\ScoutUnits\Index;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\Student;
use App\Models\StudentScoutLevel;
use App\Models\User;
use Livewire\Livewire;

test('eligible unit members are shown in a searchable student selector', function () {
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
    $scoutLevel = ScoutLevel::query()->create([
        'code' => 'SEARCHABLE-MEMBER',
        'name' => 'Golongan Pencarian Anggota',
        'sort_order' => 1,
    ]);
    $unit = ScoutUnit::query()->create([
        'academic_year_id' => $academicYear->id,
        'scout_level_id' => $scoutLevel->id,
        'name' => 'Regu Pencarian',
        'unit_type' => 'regu',
        'is_active' => true,
    ]);
    $student = Student::query()->create([
        'nis' => 'SEARCH-001',
        'name' => 'Siswa Bisa Dicari',
        'status' => 'active',
    ]);

    StudentScoutLevel::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'scout_level_id' => $scoutLevel->id,
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('selectUnit', $unit->id)
        ->assertSee('Cari nama atau NIS siswa...')
        ->assertSeeHtml('x-model="search"')
        ->assertSee('Siswa Bisa Dicari')
        ->assertSee('SEARCH-001');
});
