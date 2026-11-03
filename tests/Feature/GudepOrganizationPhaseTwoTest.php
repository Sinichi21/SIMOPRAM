<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepOrganization;
use App\Models\AcademicYear;
use App\Models\Coach;
use App\Models\GudepOfficer;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\ScoutUnit;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GudepOrganizationPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    private function schoolAndGroup(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);
        $gudep = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Garuda',
            'male_number' => '04.007',
            'is_active' => true,
        ]);

        return [$school, $gudep];
    }

    public function test_organization_screen_requires_authentication(): void
    {
        $this->get(route('administration.organization'))->assertRedirect();
    }

    public function test_officer_form_saves_decree_history(): void
    {
        [$school, $gudep] = $this->schoolAndGroup();

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('scope', 'mabi')
            ->set('position', 'Kamabigus')
            ->set('personName', 'Pembimbing Contoh')
            ->set('startedAt', '2026-08-01')
            ->set('decreeNumber', 'SK-01/2026')
            ->call('saveOfficer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gudep_officers', [
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'scope' => 'mabi',
            'decree_number' => 'SK-01/2026',
        ]);
    }

    public function test_officer_edit_from_another_gudep_is_not_accessible(): void
    {
        [$school, $gudep] = $this->schoolAndGroup();
        $other = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Lain']);
        $officer = GudepOfficer::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $other->id,
            'scope' => 'gudep',
            'position' => 'Ketua',
            'person_name' => 'Pengurus Lain',
            'started_at' => '2026-08-01',
        ]);

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->call('editOfficer', $officer->id)
            ->assertNotFound();
    }

    public function test_linking_unit_checks_active_school(): void
    {
        [$school, $gudep] = $this->schoolAndGroup();
        $otherSchool = School::factory()->create();
        app(SchoolContext::class)->set($otherSchool);
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $level = \App\Models\ScoutLevel::query()->create([
            'name' => 'Penggalang',
            'code' => 'PENGGALANG',
            'sort_order' => 2,
        ]);
        $unit = ScoutUnit::query()->create([
            'scout_level_id' => $level->id,
            'academic_year_id' => $year->id,
            'name' => 'Regu Luar',
            'unit_type' => 'regu',
        ]);
        app(SchoolContext::class)->set($school);

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('unitId', $unit->id)
            ->call('assignUnit')
            ->assertHasErrors(['unitId']);

        $this->assertNull($unit->fresh()->scout_group_id);
    }

    public function test_export_gudep_profile_is_registered_with_archived_pdf_and_qr(): void
    {
        Storage::fake('local');
        [$school, $gudep] = $this->schoolAndGroup();

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->call('publishProfile')
            ->assertHasNoErrors();

        $verification = ReportVerification::query()->where('school_id', $school->id)
            ->where('source_type', ScoutGroup::class)
            ->where('source_id', $gudep->id)->where('document_type', 'gudep_profile')
            ->firstOrFail();

        $this->assertTrue($verification->hasArchivedPdf());
        $this->assertSame(48, strlen($verification->code));
        Storage::disk('local')->assertExists($verification->file_path);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($verification->file_path));
        $this->assertSame(hash('sha256', Storage::disk('local')->get($verification->file_path)), $verification->file_sha256);
    }
}
