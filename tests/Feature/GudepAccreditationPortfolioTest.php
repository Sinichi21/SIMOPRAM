<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepAccreditationPortfolio;
use App\Models\AcademicYear;
use App\Models\GudepFormation;
use App\Models\GudepWorkProgram;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GudepAccreditationPortfolioTest extends TestCase
{
    use RefreshDatabase;

    private function actingInSchool(School $school): void
    {
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);
    }

    public function test_portfolio_shows_four_levels_without_cross_school_data(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $this->actingInSchool($school);
        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Utama']);
        $foreignGudep = ScoutGroup::query()->create(['school_id' => $other->id, 'name' => 'Gudep Rahasia']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);

        $level = ScoutLevel::query()->firstOrCreate(['code' => 'siaga'], ['name' => 'Siaga']);
        $formation = GudepFormation::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'scout_level_id' => $level->id,
            'name' => 'Barung Siaga', 'formation_type' => 'barung',
        ]);
        ScoutUnit::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'scout_level_id' => $level->id,
            'gudep_formation_id' => $formation->id, 'name' => 'Barung Merah', 'unit_type' => 'barung',
        ]);
        GudepWorkProgram::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'gudep_formation_id' => $formation->id,
            'title' => 'Latihan Siaga', 'status' => 'planned',
        ]);

        Livewire::test(GudepAccreditationPortfolio::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->assertSee('Gudep Utama')
            ->assertSee('Siaga')
            ->assertSee('Penggalang')
            ->assertSee('Penegak')
            ->assertSee('Pandega')
            ->assertDontSee('Gudep Rahasia')
            ->assertViewHas('levels', function (array $levels): bool {
                return count($levels) === 4
                    && $levels[0]['formations'] === 1
                    && $levels[0]['units'] === 1
                    && $levels[0]['programs'] === 1;
            });

        Livewire::test(GudepAccreditationPortfolio::class)
            ->set('scoutGroupId', $foreignGudep->id)
            ->assertDontSee('Gudep Rahasia')
            ->assertViewHas('levels', []);
    }
}
