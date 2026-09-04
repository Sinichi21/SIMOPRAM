<?php

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\School;
use App\Models\Semester;
use App\Models\User;
use App\Services\LpjReportService;
use App\Support\SchoolContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->user = User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]);
    $this->school = School::factory()->create(['name' => 'SD Negeri Contoh']);
    app(SchoolContext::class)->set($this->school);
    $this->academicYear = AcademicYear::factory()->create([
        'school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
    ]);
    $this->semester = Semester::query()->create([
        'school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => 'Semester Ganjil',
        'semester_number' => 1, 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'is_active' => true,
    ]);
});

test('monthly LPJ only contains activities from the selected month', function () {
    foreach ([['Latihan Agustus', '2026-08-10'], ['Latihan September', '2026-09-10']] as [$title, $date]) {
        Activity::factory()->create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'semester_id' => $this->semester->id,
            'created_by' => $this->user->id, 'title' => $title, 'start_at' => $date.' 08:00:00', 'status' => 'completed',
        ]);
    }

    $data = app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'monthly', 8);
    $html = view('reports.pdf.lpj', $data)->render();

    expect($data['activities'])->toHaveCount(1)
        ->and($html)->toContain('Latihan Agustus')->not->toContain('Latihan September')->not->toContain('LEMBAR PENGESAHAN');
});

test('semester LPJ has one cover and approval page and includes all semester months', function () {
    foreach ([['Latihan Juli', '2026-07-10'], ['Latihan November', '2026-11-10']] as [$title, $date]) {
        Activity::factory()->create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'semester_id' => $this->semester->id,
            'created_by' => $this->user->id, 'title' => $title, 'start_at' => $date.' 08:00:00', 'status' => 'published',
        ]);
    }

    $data = app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'semester');
    $html = view('reports.pdf.lpj', $data)->render();

    expect($data['activities'])->toHaveCount(2)
        ->and(substr_count($html, 'LEMBAR PENGESAHAN'))->toBe(1)
        ->and($html)->toContain('LAPORAN PERTANGGUNGJAWABAN', 'Latihan Juli', 'Latihan November');
});

test('monthly LPJ rejects a month outside the semester', function () {
    app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'monthly', 2);
})->throws(HttpException::class);
