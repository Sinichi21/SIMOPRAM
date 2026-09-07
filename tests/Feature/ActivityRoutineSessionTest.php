<?php

use App\Livewire\Activities\Index;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\School;
use App\Models\Semester;
use App\Models\User;
use App\Support\SchoolContext;
use Livewire\Livewire;

test('regular activities require and store a routine session number', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $academicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);
    $semester = Semester::query()->create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semester Ganjil',
        'semester_number' => 1,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Index::class)
        ->set('academic_year_id', $academicYear->id)
        ->set('semester_id', $semester->id)
        ->set('title', 'Latihan Rutin Sesi Dua')
        ->set('activity_type', 'regular')
        ->set('routine_session_no', null)
        ->set('start_at', '2026-09-11T15:00')
        ->set('end_at', '2026-09-11T16:00')
        ->call('save')
        ->assertHasErrors(['routine_session_no']);

    $component
        ->set('routine_session_no', 2)
        ->call('save')
        ->assertHasNoErrors();

    expect(Activity::query()
        ->where('title', 'Latihan Rutin Sesi Dua')
        ->value('routine_session_no'))->toBe(2);
});

test('special activities do not keep a routine session number', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $academicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'is_active' => true,
    ]);
    $semester = Semester::query()->create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semester Ganjil',
        'semester_number' => 1,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('academic_year_id', $academicYear->id)
        ->set('semester_id', $semester->id)
        ->set('title', 'Perkemahan Khusus')
        ->set('activity_type', 'regular')
        ->set('routine_session_no', 2)
        ->set('activity_type', 'camp')
        ->assertSet('routine_session_no', null)
        ->set('start_at', '2026-09-20T08:00')
        ->set('end_at', '2026-09-20T16:00')
        ->call('save')
        ->assertHasNoErrors();

    expect(Activity::query()
        ->where('title', 'Perkemahan Khusus')
        ->value('routine_session_no'))->toBeNull();
});
