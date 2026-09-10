<?php

use App\Livewire\Reports\PublishedDocuments\Index;
use App\Models\Letter;
use App\Models\ReportVerification;
use App\Models\School;
use App\Models\SchoolDocumentSetting;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function principalForDocuments(School $school): User
{
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create(['system_role' => 'principal', 'is_active' => true]);
    SchoolUserMembership::query()->create([
        'school_id' => $school->id, 'user_id' => $user->id,
        'is_active' => true, 'joined_at' => now()->toDateString(),
    ]);
    setPermissionsTeamId($school->id);
    $user->assignRole('principal');

    return $user;
}

function documentForPrincipal(User $principal, School $school, string $type = 'letter'): ReportVerification
{
    $service = app(ReportVerificationService::class);
    $document = $service->issueDocument(
        schoolId: $school->id, documentType: $type, sourceType: Letter::class,
        sourceId: 999, snapshotChecksum: str_repeat('a', 64), title: 'Dokumen Persetujuan',
        metadata: ['signatory_user_id' => $principal->id],
    );

    return $service->archivePdf($document, '%PDF-dokumen-untuk-disetujui', 'dokumen.pdf');
}

test('named principal reviews and approves an archived document before public verification becomes valid', function (string $type) {
    Storage::fake('local');
    $school = School::factory()->create();
    $principal = principalForDocuments($school);
    SchoolDocumentSetting::query()->create(['school_id' => $school->id, 'principal_signatory_user_id' => $principal->id]);
    $this->actingAs($principal)->withSession(['active_school_id' => $school->id]);
    $document = documentForPrincipal($principal, $school, $type);
    Storage::disk('local')->assertExists($document->file_path);
    expect($document->publicStatus())->toBe('pending');
    $this->get(route('reports.verify', $document->code))->assertSee('Menunggu Persetujuan Penandatangan');
    $this->get(route('reports.published-documents.download', $document->code))->assertOk();

    Livewire::test(Index::class)->set('status', 'pending')
        ->assertSee('Setujui dokumen')->call('approve', $document->id)->assertHasNoErrors();

    $document->refresh();
    expect($document->publicStatus())->toBe('valid');
    expect($document->signatory_approvals[0])->toMatchArray([
        'user_id' => $principal->id, 'file_sha256' => hash('sha256', '%PDF-dokumen-untuk-disetujui'),
    ]);
    $this->get(route('reports.verify', $document->code))->assertSee('Dokumen Valid');
    Livewire::test(Index::class)->call('approve', $document->id)->assertStatus(409);
    expect($document->fresh()->signatory_approvals)->toHaveCount(1);
})->with(['letter', 'grades', 'attendance', 'lpj']);

test('another principal cannot approve or download a document they did not sign', function () {
    Storage::fake('local');
    $school = School::factory()->create();
    $signer = principalForDocuments($school);
    $other = principalForDocuments($school);
    $document = documentForPrincipal($signer, $school);
    $this->actingAs($other)->withSession(['active_school_id' => $school->id]);

    Livewire::test(Index::class)->assertDontSee('Dokumen Persetujuan')
        ->call('approve', $document->id)->assertForbidden();
    $this->get(route('reports.published-documents.download', $document->code))->assertNotFound();
    expect($document->fresh()->signatory_approvals)->toBeNull();
});

test('principal cannot approve a document from another school', function () {
    Storage::fake('local');
    $school = School::factory()->create();
    $signer = principalForDocuments($school);
    $document = documentForPrincipal($signer, $school);
    $otherSchool = School::factory()->create();
    $other = principalForDocuments($otherSchool);
    $this->actingAs($other)->withSession(['active_school_id' => $otherSchool->id]);

    Livewire::test(Index::class)->call('approve', $document->id)->assertNotFound();
    expect($document->fresh()->signatory_approvals)->toBeNull();
});

test('revoked and tampered PDFs cannot be approved', function (string $state) {
    Storage::fake('local');
    $school = School::factory()->create();
    $principal = principalForDocuments($school);
    $this->actingAs($principal)->withSession(['active_school_id' => $school->id]);
    $document = documentForPrincipal($principal, $school);
    if ($state === 'revoked') {
        $document->update(['revoked_at' => now()]);
    } else {
        Storage::disk('local')->put($document->file_path, 'altered');
    }

    $component = Livewire::test(Index::class)->call('approve', $document->id);
    if ($state === 'revoked') {
        $component->assertStatus(409);
    } else {
        $component->assertHasErrors(['document']);
    }
    expect($document->fresh()->signatory_approvals)->toBeNull();
})->with(['revoked', 'tampered']);

test('all named principals must approve an LPJ and later settings changes do not change the signers', function () {
    Storage::fake('local');
    $school = School::factory()->create();
    $principal = principalForDocuments($school);
    $coordinator = principalForDocuments($school);
    $setting = SchoolDocumentSetting::query()->create([
        'school_id' => $school->id, 'principal_signatory_user_id' => $principal->id,
        'coordinator_signatory_user_id' => $coordinator->id,
    ]);
    $document = documentForPrincipal($principal, $school, 'lpj');
    $setting->update(['principal_signatory_user_id' => null, 'coordinator_signatory_user_id' => null]);
    $this->actingAs($principal)->withSession(['active_school_id' => $school->id]);

    Livewire::test(Index::class)->call('approve', $document->id)->assertHasNoErrors();
    expect($document->fresh()->publicStatus())->toBe('pending');
    $this->actingAs($coordinator);
    Livewire::test(Index::class)->set('status', 'pending')->assertSee('Dokumen Persetujuan')
        ->call('approve', $document->id)->assertHasNoErrors();

    expect($document->fresh()->publicStatus())->toBe('valid');
});

test('approval follows the signatories rendered in the PDF instead of newer school settings', function () {
    Storage::fake('local');
    $school = School::factory()->create();
    $original = principalForDocuments($school);
    $replacement = principalForDocuments($school);
    SchoolDocumentSetting::query()->create(['principal_signatory_user_id' => $replacement->id]);
    $service = app(ReportVerificationService::class);
    $document = $service->issueReportDocument($school->id, 'grades', str_repeat('a', 64));

    $document = $service->archivePdf($document, '%PDF-original', 'original.pdf', [$original->id]);

    expect($document->pendingSignatoryIds())->toBe([$original->id]);
    Storage::disk('local')->assertExists($document->file_path);
});

test('inactive membership cannot approve an assigned document', function () {
    Storage::fake('local');
    $school = School::factory()->create();
    $principal = principalForDocuments($school);
    $document = documentForPrincipal($principal, $school);
    $principal->schoolMemberships()->update(['is_active' => false]);
    $this->actingAs($principal)->withSession(['active_school_id' => $school->id]);

    Livewire::test(Index::class)->call('approve', $document->id)->assertForbidden();

    expect($document->fresh()->signatory_approvals)->toBeNull();
});

test('an archived PDF cannot be overwritten under the same verification code', function () {
    Storage::fake('local');
    $school = School::factory()->create();
    $principal = principalForDocuments($school);
    $document = documentForPrincipal($principal, $school);

    expect(fn () => app(ReportVerificationService::class)->archivePdf($document, 'replacement', 'new.pdf'))
        ->toThrow(RuntimeException::class, 'Arsip dokumen tidak dapat ditimpa.');

    expect(Storage::disk('local')->get($document->file_path))->toBe('%PDF-dokumen-untuk-disetujui');
});
