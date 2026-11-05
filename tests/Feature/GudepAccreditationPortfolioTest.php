<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepAccreditationPortfolio;
use App\Models\AcademicYear;
use App\Models\GudepAccreditationEvidenceLink;
use App\Models\GudepAccreditationIndicator;
use App\Models\GudepFormation;
use App\Models\GudepWorkProgram;
use App\Models\ReportVerification;
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

    public function test_evidence_link_rejects_foreign_gudep_and_review_requires_evidence(): void
    {
        $school = School::factory()->create();
        $this->actingInSchool($school);
        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep A']);
        $other = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep B']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);
        $valid = GudepWorkProgram::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'title' => 'Latihan Gudep A',
            'status' => 'planned',
        ]);
        $foreign = GudepWorkProgram::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $other->id,
            'academic_year_id' => $year->id, 'title' => 'Latihan Gudep B',
            'status' => 'planned',
        ]);

        $form = fn () => Livewire::test(GudepAccreditationPortfolio::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id);

        $form()->set('indicatorCode', 'ADM-01')
            ->set('indicatorTitle', 'Program terencana')
            ->call('saveIndicator')
            ->assertHasNoErrors();
        $indicator = GudepAccreditationIndicator::query()->firstOrFail();
        $this->assertSame('ADM-01', $indicator->code);

        $form()->call('markReviewed', $indicator->id)->assertStatus(409);
        $form()->call('selectIndicator', $indicator->id)
            ->set('evidenceKind', 'program')
            ->set('evidenceSourceId', $foreign->id)
            ->call('linkEvidence')
            ->assertNotFound();
        $this->assertDatabaseCount('gudep_accreditation_evidence_links', 0);

        $form()->call('selectIndicator', $indicator->id)
            ->set('evidenceKind', 'program')
            ->set('evidenceSourceId', $valid->id)
            ->call('linkEvidence')
            ->assertHasNoErrors();
        $form()->call('selectIndicator', $indicator->id)
            ->set('evidenceKind', 'program')
            ->set('evidenceSourceId', $valid->id)
            ->call('linkEvidence')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('gudep_accreditation_evidence_links', 1);
        $this->assertSame(1, GudepAccreditationEvidenceLink::query()->count());

        $form()->call('markReviewed', $indicator->id)->assertHasNoErrors();
        $this->assertSame('reviewed', $indicator->fresh()->status);
        $form()->call('selectIndicator', $indicator->id)
            ->set('evidenceSourceId', $valid->id)
            ->call('linkEvidence')
            ->assertStatus(409);
    }

    public function test_other_gudep_cannot_review_or_view_indicator(): void
    {
        $school = School::factory()->create();
        $this->actingInSchool($school);
        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep A']);
        $other = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep B']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);
        $indicator = GudepAccreditationIndicator::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'code' => 'A-1',
            'title' => 'Bukti internal', 'status' => 'draft',
        ]);

        Livewire::test(GudepAccreditationPortfolio::class)
            ->set('scoutGroupId', $other->id)
            ->set('academicYearId', $year->id)
            ->call('selectIndicator', $indicator->id)
            ->assertNotFound();

        Livewire::test(GudepAccreditationPortfolio::class)
            ->set('scoutGroupId', $other->id)
            ->set('academicYearId', $year->id)
            ->call('markReviewed', $indicator->id)
            ->assertNotFound();
    }

    public function test_deleted_source_prevents_review_of_linked_evidence(): void
    {
        $school = School::factory()->create();
        $this->actingInSchool($school);
        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep A']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);
        $program = GudepWorkProgram::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'title' => 'Program dihapus',
            'status' => 'planned',
        ]);
        $indicator = GudepAccreditationIndicator::query()->create([
            'school_id' => $school->id, 'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id, 'code' => 'P-01',
            'title' => 'Program yang dibuktikan', 'status' => 'draft',
        ]);
        GudepAccreditationEvidenceLink::query()->create([
            'school_id' => $school->id, 'indicator_id' => $indicator->id,
            'source_kind' => 'program', 'source_id' => $program->id,
        ]);
        $program->delete();

        Livewire::test(GudepAccreditationPortfolio::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('markReviewed', $indicator->id)
            ->assertStatus(409);

        $this->assertSame('draft', $indicator->fresh()->status);
    }
    public function test_only_current_valid_issued_gudep_documents_can_be_linked(): void
    {
        $school = School::factory()->create();
        $this->actingInSchool($school);
        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep A']);
        $other = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep B']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);

        $makeDocument = fn (ScoutGroup $owner, string $series, bool $revoked = false) =>
            ReportVerification::query()->create([
                'school_id' => $school->id,
                'source_type' => ScoutGroup::class,
                'source_id' => $owner->id,
                'series_key' => $series,
                'document_type' => 'gudep_profile',
                'code' => bin2hex(random_bytes(24)),
                'file_path' => 'test/gudep-profile.pdf',
                'file_sha256' => str_repeat('a', 64),
                'issued_at' => now(),
                'revoked_at' => $revoked ? now() : null,
            ]);

        $valid = $makeDocument($gudep, 'year-'.$year->id);
        $foreign = $makeDocument($other, 'year-'.$year->id);
        $wrongYear = $makeDocument($gudep, 'year-99999');
        $revoked = $makeDocument($gudep, 'year-'.$year->id, true);

        $indicator = GudepAccreditationIndicator::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'code' => 'D-01',
            'title' => 'Dokumen organisasi',
            'status' => 'draft',
        ]);

        $form = fn () => Livewire::test(GudepAccreditationPortfolio::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectIndicator', $indicator->id)
            ->set('evidenceKind', 'document');

        foreach ([$foreign, $wrongYear, $revoked] as $excluded) {
            $form()->set('evidenceSourceId', $excluded->id)
                ->call('linkEvidence')->assertNotFound();
        }

        $form()->set('evidenceSourceId', $valid->id)
            ->call('linkEvidence')->assertHasNoErrors();

        $this->assertDatabaseCount('gudep_accreditation_evidence_links', 1);
        $this->assertDatabaseHas('gudep_accreditation_evidence_links', [
            'indicator_id' => $indicator->id,
            'source_kind' => 'document',
            'source_id' => $valid->id,
        ]);

        $form()->call('markReviewed', $indicator->id)->assertHasNoErrors();
        $this->assertSame('reviewed', $indicator->fresh()->status);
    }

}
