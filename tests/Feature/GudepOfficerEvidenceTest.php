<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepOrganization;
use App\Models\GudepOfficer;
use App\Models\GudepOfficerDocument;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GudepOfficerEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private function setupGudep(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $gudep = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Arsip',
        ]);

        $officer = GudepOfficer::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'scope' => 'mabi',
            'position' => 'Kamabigus',
            'person_name' => 'Contoh Pengurus',
            'started_at' => '2026-08-01',
        ]);

        return [$school, $gudep, $officer];
    }

    public function test_sk_evidence_is_private_and_download_checks_integrity(): void
    {
        Storage::fake('local');
        [$school, $gudep, $officer] = $this->setupGudep();

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('skOfficerId', $officer->id)
            ->set('skAttachment', UploadedFile::fake()->create('sk-pengurus.pdf', 32, 'application/pdf'))
            ->call('uploadOfficerSk')
            ->assertHasNoErrors();

        $attachment = GudepOfficerDocument::query()->firstOrFail();
        $this->assertSame($school->id, $attachment->school_id);
        $this->assertSame($gudep->id, $attachment->scout_group_id);
        $this->assertSame($officer->id, $attachment->gudep_officer_id);
        $this->assertSame('local', $attachment->disk);
        Storage::disk('local')->assertExists($attachment->path);

        $this->get(route('administration.officers.sk.download', $attachment->id))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        Storage::disk('local')->put($attachment->path, 'tampered');
        $this->get(route('administration.officers.sk.download', $attachment->id))
            ->assertStatus(409);
    }

    public function test_sk_upload_rejects_an_officer_from_another_gudep(): void
    {
        Storage::fake('local');
        [$school, $gudep] = $this->setupGudep();
        $otherGudep = ScoutGroup::query()->create([
            'school_id' => $school->id,
            'name' => 'Gudep Lain',
        ]);
        $otherOfficer = GudepOfficer::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $otherGudep->id,
            'scope' => 'gudep',
            'position' => 'Ketua',
            'person_name' => 'Pengurus Lain',
            'started_at' => '2026-08-01',
        ]);

        Livewire::test(GudepOrganization::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('skOfficerId', $otherOfficer->id)
            ->set('skAttachment', UploadedFile::fake()->create('sk.pdf', 32, 'application/pdf'))
            ->call('uploadOfficerSk')
            ->assertHasErrors(['skOfficerId']);

        $this->assertDatabaseCount('gudep_officer_documents', 0);
    }

    public function test_cross_school_document_download_is_not_accessible(): void
    {
        Storage::fake('local');
        [$school, $gudep, $officer] = $this->setupGudep();
        $path = 'gudep-officer-sk/'.$school->id.'/'.$gudep->id.'/'.$officer->id.'/evidence.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 sample');

        $attachment = GudepOfficerDocument::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'gudep_officer_id' => $officer->id,
            'original_filename' => 'evidence.pdf',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 15,
            'sha256' => hash('sha256', '%PDF-1.4 sample'),
            'uploaded_by' => auth()->id(),
        ]);

        $foreign = School::factory()->create();
        app(SchoolContext::class)->set($foreign);
        setPermissionsTeamId($foreign->id);
        $this->withSession(['active_school_id' => $foreign->id]);

        $this->get(route('administration.officers.sk.download', $attachment->id))
            ->assertNotFound();
    }
}
