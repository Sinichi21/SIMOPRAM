<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Models\School;
use App\Models\Student;
use App\Support\SchoolContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentNtaIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function school(): School
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);

        return $school;
    }

    public function test_nta_is_optional_and_nis_is_retained(): void
    {
        $school = $this->school();

        $student = Student::query()->create([
            'school_id' => $school->id,
            'nta' => 'NTA-2026-001',
            'nis' => '2026001',
            'nisn' => '0012345678',
            'name' => 'Anggota Contoh',
            'status' => 'active',
        ]);

        $this->assertSame('NTA-2026-001', $student->fresh()->nta);
        $this->assertSame('2026001', $student->fresh()->nis);
        $this->assertSame('0012345678', $student->fresh()->nisn);

        $withoutNta = Student::query()->create([
            'school_id' => $school->id,
            'nis' => '2026002',
            'name' => 'Anggota Belum Ber-NTA',
        ]);
        $this->assertNull($withoutNta->fresh()->nta);
    }

    public function test_nta_must_be_unique_within_a_school(): void
    {
        $school = $this->school();
        Student::query()->create([
            'school_id' => $school->id,
            'nta' => 'NTA-ALREADY-USED',
            'nis' => '1001',
            'name' => 'Pertama',
        ]);

        $this->expectException(QueryException::class);
        Student::query()->create([
            'school_id' => $school->id,
            'nta' => 'NTA-ALREADY-USED',
            'nis' => '1002',
            'name' => 'Kedua',
        ]);
    }

    public function test_registration_accepts_nta_but_still_recognizes_nis(): void
    {
        $school = $this->school();
        $student = Student::query()->create([
            'school_id' => $school->id,
            'nta' => 'NTA-PRAMUKA-99',
            'nis' => 'NIS-99',
            'name' => 'Peserta',
            'status' => 'active',
        ]);

        Livewire::test(Register::class)
            ->set('school_id', $school->id)
            ->set('role', 'student')
            ->set('student_identifier', 'NTA-PRAMUKA-99')
            ->call('findStudent')
            ->assertSet('matched_student_id', $student->id);

        Livewire::test(Register::class)
            ->set('school_id', $school->id)
            ->set('role', 'student')
            ->set('student_identifier', 'NIS-99')
            ->call('findStudent')
            ->assertSet('matched_student_id', $student->id);
    }

    public function test_registration_does_not_guess_when_nta_matches_another_students_nis(): void
    {
        $school = $this->school();
        Student::query()->create([
            'school_id' => $school->id,
            'nta' => 'NUMBER-X',
            'nis' => 'NIS-A',
            'name' => 'Anggota A',
            'status' => 'active',
        ]);
        Student::query()->create([
            'school_id' => $school->id,
            'nta' => 'NUMBER-Y',
            'nis' => 'NUMBER-X',
            'name' => 'Anggota B',
            'status' => 'active',
        ]);

        Livewire::test(Register::class)
            ->set('school_id', $school->id)
            ->set('role', 'student')
            ->set('student_identifier', 'NUMBER-X')
            ->call('findStudent')
            ->assertSet('matched_student_id', null);
    }
}
