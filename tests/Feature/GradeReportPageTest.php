<?php

use App\Livewire\Reports\Grades;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;

test('grade report route mounts livewire with available filter properties', function (bool $hasYear) {
    $school = School::factory()->create();
    if ($hasYear) {
        AcademicYear::factory()->create(['school_id' => $school->id, 'is_active' => true]);
    }
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->withSession(['active_school_id' => $school->id])
        ->get(route('reports.grades'))
        ->assertOk()
        ->assertSeeLivewire(Grades::class)
        ->assertSee('Rekap Nilai')
        ->assertSee('Export PDF');
})->with([true, false]);
