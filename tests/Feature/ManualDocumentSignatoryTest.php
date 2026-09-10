<?php

use App\Livewire\Letters\Index;
use App\Livewire\Settings\SchoolDocuments;
use App\Models\Coach;
use App\Models\School;
use App\Models\SchoolDocumentSetting;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Services\DocumentSignatoryService;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    app(SchoolContext::class)->set(School::factory()->create());
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
});

test('manual signatories replace account selection without requesting approval and render in reports', function () {
    Storage::fake('local');
    $school = app(SchoolContext::class)->school();
    $principal = User::factory()->create(['system_role' => 'principal']);
    SchoolUserMembership::query()->create(['school_id' => $school->id, 'user_id' => $principal->id, 'is_active' => true]);
    SchoolDocumentSetting::query()->create(['principal_signatory_user_id' => $principal->id]);
    $component = Livewire::test(SchoolDocuments::class);
    foreach (DocumentSignatoryService::SLOTS as $slot => $property) {
        $component->set('signatorySources.'.$slot, 'manual')
            ->set('manualSignatories.'.$slot.'.name', 'Pejabat Manual '.$slot)
            ->set('manualSignatories.'.$slot.'.position', 'Jabatan '.$slot)
            ->set('manualSignatories.'.$slot.'.identifier_type', 'NIP')
            ->set('manualSignatories.'.$slot.'.identifier_number', '001234');
    }
    $component->call('save')->assertHasNoErrors();
    $setting = SchoolDocumentSetting::query()->sole();
    expect($setting->principal_signatory_user_id)->toBeNull();
    Livewire::test(Index::class, ['direction' => 'outgoing'])
        ->assertSet('signatory_source', 'manual')
        ->assertSet('signatory_user_id', null)
        ->assertSet('signatory_name', 'Pejabat Manual default_letter');
    foreach (DocumentSignatoryService::SLOTS as $slot => $property) {
        expect(app(DocumentSignatoryService::class)->configured($setting, $slot, $school->id))->toMatchArray(['user_id' => null, 'name' => 'Pejabat Manual '.$slot, 'identity' => 'NIP. 001234']);
    }
    $html = view('reports.pdf.partials.signature', ['school' => $school, 'documentSetting' => $setting])->render();
    expect($html)->toContain('Pejabat Manual principal', 'Pejabat Manual responsible');
    $service = app(ReportVerificationService::class);
    $document = $service->issueDocument(schoolId: $school->id, documentType: 'grades', sourceType: 'test', sourceId: 1, snapshotChecksum: str_repeat('a', 64), title: 'Laporan manual');
    $document = $service->archivePdf($document, '%PDF-test', 'manual.pdf');
    expect($document->required_signatory_ids)->toBe([]);
    expect($document->publicStatus())->toBe('valid');
    Livewire::test(SchoolDocuments::class)->assertSet('signatorySources.principal', 'manual');
    Livewire::test(SchoolDocuments::class)->set('signatorySources.principal', 'user')->set('principalSignatoryUserId', $principal->id)->call('save')->assertHasNoErrors();
    expect(app(DocumentSignatoryService::class)->configured($setting->fresh(), 'principal', $school->id)['user_id'])->toBe($principal->id);
});

test('manual name and position are required and account authorization is preserved', function () {
    Livewire::test(SchoolDocuments::class)->set('signatorySources.principal', 'manual')->call('save')->assertHasErrors(['manualSignatories.principal.name', 'manualSignatories.principal.position']);
    $foreign = User::factory()->create();
    Livewire::test(SchoolDocuments::class)->set('principalSignatoryUserId', $foreign->id)->call('save')->assertHasErrors('signatory');
    $this->assertDatabaseCount('school_document_settings', 0);
});

test('master coach without account can fill every default slot without duplicate manual data', function () {
    $school = app(SchoolContext::class)->school();
    $coach = Coach::query()->create(['name' => 'Pembina Master', 'position' => 'Pembina Penggalang', 'nip' => '12345', 'is_active' => true]);
    $component = Livewire::test(SchoolDocuments::class)->set('signatorySources.responsible', 'coach')->assertSee('Pembina Master');
    foreach (DocumentSignatoryService::SLOTS as $slot => $property) {
        $component->set('signatorySources.'.$slot, 'coach')->set('coachSignatories.'.$slot, $coach->id);
    }
    $component->call('save')->assertHasNoErrors();
    $setting = SchoolDocumentSetting::query()->sole();
    foreach (DocumentSignatoryService::SLOTS as $slot => $property) {
        expect($setting->getAttribute($slot.'_signatory_user_id'))->toBeNull();
        expect(app(DocumentSignatoryService::class)->configured($setting, $slot, $school->id))->toMatchArray(['user_id' => null, 'name' => 'Pembina Master', 'identity' => 'NTA. 12345']);
    }
    $coach->update(['name' => 'Pembina Diperbarui']);
    expect(app(DocumentSignatoryService::class)->configured($setting, 'responsible', $school->id)['name'])->toBe('Pembina Diperbarui');
    Livewire::test(SchoolDocuments::class)->assertSet('signatorySources.responsible', 'coach')->assertSet('coachSignatories.responsible', $coach->id);
    Livewire::test(Index::class, ['direction' => 'outgoing'])->assertSet('signatory_source', 'manual')->assertSet('signatory_name', 'Pembina Diperbarui');
    $this->assertDatabaseCount('users', 1);
});

test('master coach selection rejects inactive or foreign coaches', function () {
    $coach = Coach::query()->create(['name' => 'Nonaktif', 'is_active' => false]);
    Livewire::test(SchoolDocuments::class)->set('signatorySources.responsible', 'coach')->set('coachSignatories.responsible', $coach->id)->call('save')->assertHasErrors('signatory');
    $coach->is_active = true;
    $coach->school_id = School::factory()->create()->id;
    $coach->save();
    Livewire::test(SchoolDocuments::class)->set('signatorySources.responsible', 'coach')->set('coachSignatories.responsible', $coach->id)->call('save')->assertHasErrors('signatory');
    $this->assertDatabaseCount('school_document_settings', 0);
});
