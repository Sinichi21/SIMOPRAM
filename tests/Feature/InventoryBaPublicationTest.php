<?php

use App\Livewire\Reports\PublishedDocuments\Index as PublishedDocuments;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\LetterField;
use App\Models\LetterType;
use App\Models\School;
use App\Models\SchoolDocumentSetting;
use App\Models\ScoutAdministrationProfile;
use App\Models\User;
use App\Services\Inventory\InventoryLoanDocumentService;
use App\Services\Inventory\InventoryLoanService;
use App\Services\Inventory\InventoryStockService;
use App\Services\LetterAdministrationBootstrapService;
use App\Services\LetterNumberService;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function baPublicationLoan(): InventoryLoan
{
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    test()->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->withSession(['active_school_id' => $school->id]);
    test()->travelTo(now()->setDate(2026, 11, 1)->setTime(6, 31));
    Storage::fake('local');
    SchoolDocumentSetting::query()->create(['gudep_male_number' => '04.007', 'gudep_female_number' => '04.008']);
    app(LetterAdministrationBootstrapService::class)->ensureForSchool($school->id);
    ScoutAdministrationProfile::query()->where('type', 'mabigus')->firstOrFail()->update([
        'letterhead_title' => 'MAJELIS PEMBIMBING GUGUSDEPAN', 'letterhead_subtitle' => 'PANGKALAN SD 1 TEST',
        'letterhead_address' => 'Alamat MABI', 'number_format' => 'CUSTOM/{sequence}/{year}',
    ]);
    $category = InventoryCategory::query()->create(['name' => 'Peralatan', 'is_active' => true]);
    $item = InventoryItem::query()->create([
        'category_id' => $category->id, 'item_code' => 'BA-001', 'name' => 'Tali',
        'tracking_type' => 'quantity', 'unit' => 'meter', 'is_active' => true,
    ]);
    $owner = InventoryOwner::query()->create(['owner_type' => 'school', 'display_name' => 'Sekolah', 'is_active' => true]);
    $location = InventoryLocation::query()->create(['name' => 'Gudang', 'location_type' => 'storage', 'is_active' => true]);
    app(InventoryStockService::class)->recordMovement($item->id, $owner->id, $location->id, 'receipt', 5);
    $service = app(InventoryLoanService::class);
    $loan = $service->requestLoan(
        ['type' => 'manual_external', 'name' => 'test', 'organization' => 'sd 1 test', 'contact' => '082339557410'],
        [['item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id, 'quantity' => 2]], 'test 2',
    );
    $service->approve($loan->id);
    $service->recordHandover($loan->id, [['loan_item_id' => $loan->items->first()->id, 'quantity' => 2]], now());

    return $loan;
}

it('publishes handover and return BA with MABI numbers QR and one shared archive', function (string $type) {
    $loan = baPublicationLoan();
    if ($type === 'return') {
        app(InventoryLoanService::class)->recordReturn($loan->id, [
            ['loan_item_id' => $loan->items->first()->id, 'quantity_returned' => 2],
        ], now());
    }
    $service = app(InventoryLoanDocumentService::class);

    $document = $service->issue($loan->id, $type);
    $verification = $document->verification;

    expect($document->document_number)->toBe('1/02/XI.26/04.007-008-C');
    expect($verification)->not->toBeNull();
    expect($verification->file_path)->toBe($document->storage_path);
    expect($verification->file_sha256)->toBe($document->sha256);
    expect($verification->metadata)->toMatchArray(['letter_type_code' => '02', 'letter_field_code' => 'C', 'loan_number' => $loan->loan_number]);
    expect($verification->publicStatus())->toBe('valid');
    $binary = app(ReportVerificationService::class)->archivedPdfBinary($verification);
    expect($binary)->toStartWith('%PDF');
    expect($binary)->toMatch('/\\/Subtype\\s*\\/Image\\b/');
    expect($document->sha256)->toBe(hash('sha256', $binary));
    expect($service->issue($loan->id, $type)->id)->toBe($document->id);
    test()->assertDatabaseCount('report_verifications', 1);
    test()->assertDatabaseHas('letter_number_sequences', ['administration_type' => 'mabigus', 'year' => 2026, 'last_number' => 1]);
    Livewire::test(PublishedDocuments::class)->set('search', $document->document_number)->assertSee($document->document_number);
    test()->get(route('reports.verify', ['code' => $verification->code]))
        ->assertSee('Dokumen Valid')->assertSee($document->document_number)
        ->assertDontSee('082339557410')->assertDontSee('sd 1 test');
})->with(['handover', 'return']);

it('renders the MABI letterhead aligned borrower rows and invoice reference without consuming a number in preview', function () {
    $loan = baPublicationLoan();
    $loan->update(['borrower_name' => '<script>alert(1)</script>']);
    $data = app(InventoryLoanDocumentService::class)->previewData($loan->fresh(), 'handover');

    $html = view('inventory.documents.preview', $data)->render();

    expect($html)->toContain('MAJELIS PEMBIMBING GUGUSDEPAN', 'PANGKALAN SD 1 TEST', 'Alamat MABI', $loan->loan_number, '01-11-2026 06:31')
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')->not->toContain('<script>alert(1)</script>');
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $colons = $xpath->query('//table[@class="identity"]/tr/td[2]');
    expect($colons->length)->toBe(5);
    foreach ($colons as $colon) {
        expect(trim($colon->textContent))->toBe(':');
    }
    test()->assertDatabaseCount('letter_number_sequences', 0);
    test()->assertDatabaseCount('report_verifications', 0);
});

it('gives a correction a new number and QR revokes the previous QR and retains old bytes', function () {
    $loan = baPublicationLoan();
    $service = app(InventoryLoanDocumentService::class);
    $first = $service->issue($loan->id, 'handover');
    $original = Storage::disk('local')->get($first->storage_path);

    $second = $service->correct($first->id, 'Perbaikan data administratif berita acara.');

    expect($second->document_number)->toBe('2/02/XI.26/04.007-008-C');
    expect($second->verification->code)->not->toBe($first->verification->code);
    expect($first->fresh()->verification->publicStatus())->toBe('revoked');
    expect(Storage::disk('local')->get($first->storage_path))->toBe($original);
    expect($second->verification->publicStatus())->toBe('valid');
    test()->get(route('reports.verify', ['code' => $first->verification->code]))->assertSee('Dokumen Dicabut');
    test()->assertDatabaseCount('report_verifications', 2);
});

it('rolls back the number BA verification and PDF when archival fails after writing', function () {
    $loan = baPublicationLoan();
    $verificationService = app(ReportVerificationService::class);
    $instance = Mockery::mock(ReportVerificationService::class)->makePartial();
    $instance->shouldReceive('archivePdf')->once()->andReturnUsing(function (...$arguments) use ($verificationService) {
        $verificationService->archivePdf(...$arguments);
        throw new RuntimeException('Simulated failure after archival');
    });
    app()->instance(ReportVerificationService::class, $instance);

    expect(fn () => app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover'))
        ->toThrow(RuntimeException::class, 'Simulated failure after archival');

    test()->assertDatabaseCount('inventory_loan_documents', 0);
    test()->assertDatabaseCount('report_verifications', 0);
    test()->assertDatabaseCount('letter_number_sequences', 0);
    expect(Storage::disk('local')->allFiles('report-verifications'))->toBe([]);
});

it('keeps the previous BA and QR valid when a correction cannot be archived', function () {
    $loan = baPublicationLoan();
    $service = app(InventoryLoanDocumentService::class);
    $first = $service->issue($loan->id, 'handover');
    $instance = Mockery::mock(ReportVerificationService::class)->makePartial();
    $instance->shouldReceive('archivePdf')->once()->andThrow(new RuntimeException('Archive unavailable'));
    app()->instance(ReportVerificationService::class, $instance);

    expect(fn () => $service->correct($first->id, 'Perbaikan administrasi yang gagal disimpan.'))->toThrow(RuntimeException::class);

    expect($first->fresh()->status)->toBe('issued');
    expect($first->verification->publicStatus())->toBe('valid');
    test()->assertDatabaseCount('inventory_loan_documents', 1);
    test()->assertDatabaseCount('report_verifications', 1);
    test()->assertDatabaseHas('letter_number_sequences', ['last_number' => 1]);
});

it('shares the MABI outgoing sequence without changing the configured format for other letters', function () {
    $loan = baPublicationLoan();
    app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
    $schoolId = (int) $loan->school_id;
    $type = LetterType::query()->where('code', '02')->firstOrFail();
    $field = LetterField::query()->where('code', 'C')->firstOrFail();

    $number = app(LetterNumberService::class)->nextOutgoing($schoolId, now(), $type, $field);

    expect($number)->toBe('CUSTOM/2/2026');
    expect(ScoutAdministrationProfile::query()->where('type', 'mabigus')->value('number_format'))->toBe('CUSTOM/{sequence}/{year}');
});

it('blocks inventory reprinting after revocation in published documents', function () {
    $loan = baPublicationLoan();
    $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
    app(ReportVerificationService::class)->revoke($document->verification, 'Berita acara dicabut untuk pemeriksaan.');

    test()->post(route('inventory.loans.documents.reprint', ['document' => $document->id]), ['reason' => 'Salinan'])
        ->assertStatus(409);

    test()->assertDatabaseCount('inventory_loan_document_prints', 0);
});
