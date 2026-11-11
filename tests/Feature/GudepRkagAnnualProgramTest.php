<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Administration\GudepProgramPlanner;
use App\Models\AcademicYear;
use App\Models\GudepProgramSheet;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GudepRkagAnnualProgramTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{ScoutGroup, AcademicYear} */
    private function context(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $gudep = ScoutGroup::query()->create([
            'name' => 'Gudep Pengujian RKAG', 'male_number' => '04.191',
            'female_number' => '04.192',
        ]);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        return [$gudep, $year];
    }

    public function test_annual_rkag_saves_computed_amount_without_creating_cash_or_budget(): void
    {
        [$gudep, $year] = $this->context();
        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('title', 'Rencana Anggaran Persami')
            ->set('entries', [[
                'key' => 'persami', 'parent_key' => null,
                'section' => 'keuangan', 'description' => 'Persami',
                'finance_type' => 'expense', 'currency' => 'IDR',
                'amount' => 999999999, // Never trust a client-side total.
                'rkag_lines' => [
                    ['description' => 'Konsumsi', 'volume' => 25, 'unit' => 'orang', 'unit_price' => 15000],
                    ['description' => 'Tali', 'volume' => 3, 'unit' => 'buah', 'unit_price' => 12000],
                ],
            ]])
            ->call('save')->assertHasNoErrors();

        $sheet = GudepProgramSheet::query()->firstOrFail();
        $this->assertSame(411000, $sheet->entries[0]['amount']);
        $this->assertSame(375000, $sheet->entries[0]['rkag_lines'][0]['subtotal']);
        $this->assertSame(36000, $sheet->entries[0]['rkag_lines'][1]['subtotal']);
        $this->assertDatabaseCount('gudep_cash_entries', 0);
        $this->assertDatabaseCount('gudep_finance_budgets', 0);

        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)->set('academicYearId', $year->id)
            ->call('edit', $sheet->id)
            ->assertSet('entries.0.rkag_lines.0.volume', 25)
            ->set('entries.0.rkag_lines.0.volume', 30)
            ->call('save')->assertHasNoErrors();

        $this->assertSame(486000, $sheet->fresh()->entries[0]['amount']);
        $this->assertDatabaseCount('gudep_cash_entries', 0);
    }

    public function test_bad_rkag_line_does_not_save_any_program(): void
    {
        [$gudep, $year] = $this->context();
        Livewire::test(GudepProgramPlanner::class)
            ->set('scoutGroupId', $gudep->id)->set('academicYearId', $year->id)
            ->set('title', 'Program RKAG Invalid')
            ->set('entries', [[
                'key' => 'bad', 'section' => 'keuangan', 'description' => 'Persami',
                'finance_type' => 'expense', 'amount' => 1000000,
                'rkag_lines' => [[
                    'description' => 'Konsumsi', 'volume' => 0, 'unit' => 'orang', 'unit_price' => 15000,
                ]],
            ]])
            ->call('save')->assertHasErrors(['entries.0.rkag_lines.0.volume']);

        $this->assertDatabaseCount('gudep_program_sheets', 0);
        $this->assertDatabaseCount('gudep_cash_entries', 0);
    }
}
