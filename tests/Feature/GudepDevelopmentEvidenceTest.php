<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepDevelopment;
use App\Models\AcademicYear;
use App\Models\GudepAchievement;
use App\Models\GudepAchievementEvidence;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GudepDevelopmentEvidenceTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{School, ScoutGroup, AcademicYear, GudepAchievement} */
    private function context(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $gudep = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Arsip Pembinaan',
        ]);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $achievement = GudepAchievement::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'kind' => 'achievement',
            'title' => 'Juara Lomba',
            'awarded_at' => '2026-10-01',
        ]);

        return [$school, $gudep, $year, $achievement];
    }

    public function test_certificate_is_archived_privately_and_tampering_is_rejected(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year, $achievement] = $this->context();

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('evidenceAchievementId', $achievement->id)
            ->set('evidenceAttachment', UploadedFile::fake()->create('piagam.pdf', 32, 'application/pdf'))
            ->call('uploadEvidence')
            ->assertHasNoErrors();

        $evidence = GudepAchievementEvidence::query()->firstOrFail();
        $this->assertSame($school->id, $evidence->school_id);
        $this->assertSame($gudep->id, $evidence->scout_group_id);
        Storage::disk('local')->assertExists($evidence->path);

        $this->get(route('administration.development.evidence.download', $evidence->id))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        Storage::disk('local')->put($evidence->path, 'tampered certificate');
        $this->get(route('administration.development.evidence.download', $evidence->id))
            ->assertStatus(409);
    }

    public function test_evidence_upload_cannot_attach_to_another_gudep(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year] = $this->context();
        $other = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Lain',
        ]);
        $foreign = GudepAchievement::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $other->id,
            'academic_year_id' => $year->id,
            'kind' => 'achievement',
            'title' => 'Prestasi Lain',
            'awarded_at' => '2026-10-01',
        ]);

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('evidenceAchievementId', $foreign->id)
            ->set('evidenceAttachment', UploadedFile::fake()->create('bukti.pdf', 32, 'application/pdf'))
            ->call('uploadEvidence')
            ->assertHasErrors(['evidenceAchievementId']);

        $this->assertDatabaseCount('gudep_achievement_evidences', 0);
    }

    public function test_school_boundary_prevents_evidence_download(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year, $achievement] = $this->context();
        $path = 'gudep-achievement-evidences/'.$school->id.'/'.$gudep->id.'/'.$achievement->id.'/bukti.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 evidence');

        $evidence = GudepAchievementEvidence::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'gudep_achievement_id' => $achievement->id,
            'original_filename' => 'bukti.pdf',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 17,
            'sha256' => hash('sha256', '%PDF-1.4 evidence'),
            'uploaded_by' => auth()->id(),
        ]);

        $anotherSchool = School::factory()->create();
        app(SchoolContext::class)->set($anotherSchool);
        setPermissionsTeamId($anotherSchool->id);
        $this->withSession(['active_school_id' => $anotherSchool->id]);

        $this->get(route('administration.development.evidence.download', $evidence->id))
            ->assertNotFound();
    }
    public function test_adding_supporting_evidence_creates_a_new_official_portfolio_revision(): void
    {
        Storage::fake('local');
        [$school, $gudep, $year, $achievement] = $this->context();

        $publish = fn () => Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('publishPortfolio')
            ->assertHasNoErrors();

        $publish();
        $first = ReportVerification::query()
            ->where('document_type', 'gudep_development')->firstOrFail();
        $firstBytes = Storage::disk('local')->get($first->file_path);

        Livewire::test(GudepDevelopment::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('evidenceAchievementId', $achievement->id)
            ->set('evidenceAttachment', UploadedFile::fake()->create('sertifikat.pdf', 32, 'application/pdf'))
            ->call('uploadEvidence')
            ->assertHasNoErrors();

        $publish();
        $this->assertDatabaseCount('report_verifications', 2);
        $this->assertSame('superseded', $first->fresh()->publicStatus());
        $this->assertSame($firstBytes, Storage::disk('local')->get($first->file_path));
    }

}
