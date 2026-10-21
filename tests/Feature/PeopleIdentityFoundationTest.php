<?php

use App\Models\Coach;
use App\Models\Person;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('people can be linked to accounts and multiple school profiles without replacing legacy fields', function () {
    $person = Person::create([
        'name' => 'Contoh Identitas',
        'gender' => 'L',
        'birth_place' => 'Denpasar',
        'birth_date' => '2000-01-01',
    ]);

    $user = User::factory()->create([
        'person_id' => $person->id,
    ]);

    $schoolOne = School::factory()->create();
    $schoolTwo = School::factory()->create();

    $studentOne = Student::factory()->create([
        'school_id' => $schoolOne->id,
        'person_id' => $person->id,
        'name' => 'Contoh Identitas',
    ]);

    $studentTwo = Student::factory()->create([
        'school_id' => $schoolTwo->id,
        'person_id' => $person->id,
        'name' => 'Contoh Identitas',
    ]);

    $coach = Coach::query()->create([
        'school_id' => $schoolOne->id,
        'person_id' => $person->id,
        'name' => 'Contoh Identitas',
        'is_active' => true,
    ]);

    expect($user->person->is($person))->toBeTrue()
        ->and($studentOne->person->is($person))->toBeTrue()
        ->and($studentTwo->person->is($person))->toBeTrue()
        ->and($coach->person->is($person))->toBeTrue()
        ->and($person->users()->count())->toBe(1)
        ->and($person->studentProfiles()->count())->toBe(2)
        ->and($person->coachProfiles()->count())->toBe(1);

    expect(Schema::hasColumn('students', 'nis'))->toBeTrue()
        ->and(Schema::hasColumn('students', 'name'))->toBeTrue()
        ->and(Schema::hasColumn('coaches', 'nip'))->toBeTrue()
        ->and(Schema::hasColumn('coaches', 'name'))->toBeTrue();
});
