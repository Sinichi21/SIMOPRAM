<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepOrganization;
use App\Models\AcademicYear;
use App\Models\Coach;
use App\Models\GudepOfficer;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\ScoutLevel;
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
        $level = ScoutLevel::query()->create([
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
    public function test_a_different_academic_year_has_its_own_document_series(): void
    {
        Storage::fake('local');
        [$school, $gudep] = $this->schoolAndGroup();

        $firstYear = AcademicYear::query()->create([
            'name' => '2025/2026', 'start_date' => '2025-07-01',
            'end_date' => '2026-06-30', 'is_active' => false,
        ]);
        $secondYear = AcademicYear::query()->create([
            'name' => '2026/2027', 'start_date' => '2026-07-01',
            'end_date' => '2027-06-30', 'is_active' => true,
        ]);

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $firstYear->id)
            ->call('publishMembers')
            ->assertHasNoErrors();

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $secondYear->id)
            ->call('publishMembers')
            ->assertHasNoErrors();

        $documents = ReportVerification::query()
            ->where('school_id', $school->id)->where('document_type', 'gudep_members')
            ->orderBy('id')->get();

        $this->assertCount(2, $documents);
        $this->assertSame(['year-'.$firstYear->id, 'year-'.$secondYear->id],
            $documents->pluck('series_key')->all());
        $this->assertSame([1, 1], $documents->pluck('revision')->all());
        $this->assertSame(['valid', 'valid'], $documents->map->publicStatus()->all());
    }

    public function test_identical_publication_is_not_duplicated_and_revision_keeps_old_pdf(): void
    {
        Storage::fake('local');
        [$school, $gudep] = $this->schoolAndGroup();

        Livewire::test(GudepOrganization::class)->call('publishProfile')->assertHasNoErrors();
        $first = ReportVerification::query()->where('document_type', 'gudep_profile')->firstOrFail();
        $oldBinary = Storage::disk('local')->get($first->file_path);

        Livewire::test(GudepOrganization::class)->call('publishProfile')->assertHasNoErrors();
        $this->assertSame(1, ReportVerification::query()->where('document_type', 'gudep_profile')->count());

        GudepOfficer::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'scope' => 'gudep',
            'position' => 'Ketua',
            'person_name' => 'Pengurus Tambahan',
            'started_at' => '2026-07-01',
        ]);

        Livewire::test(GudepOrganization::class)->call('publishProfile')->assertHasNoErrors();
        $documents = ReportVerification::query()->where('document_type', 'gudep_profile')
            ->orderBy('revision')->get();

        $this->assertCount(2, $documents);
        $this->assertSame('superseded', $first->fresh()->publicStatus());
        $this->assertSame('valid', $documents->last()->publicStatus());
        $this->assertSame($oldBinary, Storage::disk('local')->get($first->file_path));
    }

    public function test_member_book_has_restricted_public_title(): void
    {
        Storage::fake('local');
        [, $gudep] = $this->schoolAndGroup();

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->call('publishMembers')->assertHasNoErrors();

        $document = ReportVerification::query()
            ->where('document_type', 'gudep_members')->firstOrFail();

        $this->assertSame('Terbatas', data_get($document->metadata, 'security_classification'));
        $this->assertSame('Dokumen berklasifikasi Terbatas', $document->publicTitle());
    }

    public function test_four_golongan_formation_creates_ambalan_and_links_matching_unit(): void
    {
        [$school, $gudep] = $this->schoolAndGroup();
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $level = ScoutLevel::query()->create([
            'name' => 'Penegak',
            'code' => 'PENEGAK',
            'sort_order' => 3,
        ]);
        $unit = ScoutUnit::query()->create([
            'school_id' => $school->id,
            'scout_level_id' => $level->id,
            'academic_year_id' => $year->id,
            'name' => 'Sangga Elang',
            'unit_type' => 'sangga',
        ]);

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('formationScoutLevelId', $level->id)
            ->set('formationYearId', $year->id)
            ->set('formationName', 'Ambalan Diponegoro')
            ->set('formationGender', 'putra')
            ->call('saveFormation')
            ->assertHasNoErrors();

        $formation = \App\Models\GudepFormation::query()->firstOrFail();
        $this->assertSame('ambalan', $formation->formation_type);

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('unitId', $unit->id)
            ->set('unitFormationId', $formation->id)
            ->call('assignUnit')
            ->assertHasNoErrors();

        $this->assertSame($formation->id, $unit->fresh()->gudep_formation_id);
        $this->assertSame($gudep->id, $unit->fresh()->scout_group_id);
    }

    public function test_unit_cannot_be_linked_to_formation_in_another_golongan(): void
    {
        [$school, $gudep] = $this->schoolAndGroup();
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $siaga = ScoutLevel::query()->create(['name' => 'Siaga', 'code' => 'SIAGA']);
        $penggalang = ScoutLevel::query()->create(['name' => 'Penggalang', 'code' => 'PENGGALANG']);

        $formation = \App\Models\GudepFormation::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'scout_level_id' => $siaga->id,
            'academic_year_id' => $year->id,
            'name' => 'Perindukan Satu',
            'formation_type' => 'perindukan',
        ]);
        $unit = ScoutUnit::query()->create([
            'school_id' => $school->id,
            'scout_level_id' => $penggalang->id,
            'academic_year_id' => $year->id,
            'name' => 'Regu Cendrawasih',
            'unit_type' => 'regu',
        ]);

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('unitId', $unit->id)
            ->set('unitFormationId', $formation->id)
            ->call('assignUnit')
            ->assertHasErrors(['unitFormationId']);

        $this->assertNull($unit->fresh()->scout_group_id);
    }

}
