<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Services\Inventory\InventoryLoanDocumentService;
use App\Services\Inventory\InventoryLoanService;
use App\Services\Inventory\InventoryStockService;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryLoanDocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);
    }

    public function test_issued_handover_pdf_is_stored_with_matching_hash_and_is_idempotent(): void
    {
        $loan = $this->makeLoan(true);
        $service = app(InventoryLoanDocumentService::class);

        $first = $service->issue($loan->id, 'handover');

        $this->assertSame('issued', $first->status);
        $this->assertSame(1, $first->version);
        $this->assertSame('handover', $first->document_type);
        $this->assertNotEmpty($first->document_number);
        Storage::disk('local')->assertExists($first->storage_path);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($first->storage_path)), $first->sha256);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($first->storage_path));

        $second = $service->issue($loan->id, 'handover');

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('inventory_loan_documents', 1);
    }

    public function test_cannot_issue_ba_without_a_physical_event(): void
    {
        $loan = $this->makeLoan(false);

        $this->expectException(ValidationException::class);
        app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
    }

    public function test_return_ba_cannot_be_issued_from_handover_events(): void
    {
        $loan = $this->makeLoan(true);

        $this->expectException(ValidationException::class);
        app(InventoryLoanDocumentService::class)->issue($loan->id, 'return');
    }

    public function test_cannot_issue_document_for_another_school(): void
    {
        $loan = $this->makeLoan(true);
        app(SchoolContext::class)->set(School::factory()->create());

        $this->expectException(ModelNotFoundException::class);
        app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
    }

    public function test_issued_pdf_can_be_opened_with_matching_checksum(): void
    {
        $loan = $this->makeLoan(true);
        $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');

        $response = $this->get(route('inventory.loans.documents.show', ['document' => $document->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(Storage::disk('local')->get($document->storage_path), $response->getContent());
    }

    public function test_tampered_pdf_is_rejected_before_download(): void
    {
        $loan = $this->makeLoan(true);
        $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
        Storage::disk('local')->put($document->storage_path, 'tampered');

        $this->get(route('inventory.loans.documents.show', ['document' => $document->id]))
            ->assertStatus(409);
    }

    public function test_issued_pdf_cannot_be_opened_from_another_school(): void
    {
        $loan = $this->makeLoan(true);
        $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
        $otherSchool = School::factory()->create();
        app(SchoolContext::class)->set($otherSchool);
        $this->withSession(['active_school_id' => $otherSchool->id]);

        $this->get(route('inventory.loans.documents.show', ['document' => $document->id]))
            ->assertNotFound();
    }

    public function test_user_without_school_context_cannot_access_ba_routes(): void
    {
        $loan = $this->makeLoan(true);
        $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');

        // school.required rejects users without an active school before permission checks.
        $this->actingAs(User::factory()->create(['system_role' => null]));

        $this->post(route('inventory.loans.documents.issue', [
            'loan' => $loan->id,
            'type' => 'handover',
        ]))->assertStatus(409);

        $this->get(route('inventory.loans.documents.show', [
            'document' => $document->id,
        ]))->assertStatus(409);

        $this->post(route('inventory.loans.documents.reprint', [
            'document' => $document->id,
        ]), ['reason' => 'Salinan'])->assertStatus(409);
    }

    public function test_school_member_without_document_permissions_receives_403(): void
    {
        $loan = $this->makeLoan(true);
        $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
        $schoolId = app(SchoolContext::class)->id();

        $member = User::factory()->create(['system_role' => null]);
        SchoolUserMembership::query()->create([
            'school_id' => $schoolId,
            'user_id' => $member->id,
            'is_active' => true,
        ]);
        $this->actingAs($member)->withSession(['active_school_id' => $schoolId]);

        $this->get(route('inventory.loans.documents.show', [
            'document' => $document->id,
        ]))->assertForbidden();

        $this->post(route('inventory.loans.documents.issue', [
            'loan' => $loan->id,
            'type' => 'handover',
        ]))->assertForbidden();

        $this->post(route('inventory.loans.documents.reprint', [
            'document' => $document->id,
        ]), ['reason' => 'Arsip'])->assertForbidden();

        $this->assertDatabaseCount('inventory_loan_document_prints', 0);
    }

    public function test_reprint_requires_reason_and_creates_audit_record(): void
    {
        $loan = $this->makeLoan(true);
        $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
        $url = route('inventory.loans.documents.reprint', ['document' => $document->id]);

        $this->from(route('inventory.loans.index'))
            ->post($url, ['reason' => ''])
            ->assertSessionHasErrors('reason');
        $this->assertDatabaseCount('inventory_loan_document_prints', 0);

        $this->post($url, ['reason' => 'Arsip pengganti untuk sekolah'])
            ->assertRedirect(route('inventory.loans.documents.show', ['document' => $document->id]));

        $this->assertDatabaseHas('inventory_loan_document_prints', [
            'document_id' => $document->id,
            'purpose' => 'reprint',
            'reason' => 'Arsip pengganti untuk sekolah',
        ]);
    }

    public function test_tampered_document_cannot_be_logged_as_reprinted(): void
    {
        $loan = $this->makeLoan(true);
        $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
        Storage::disk('local')->put($document->storage_path, 'tampered');

        $this->post(route('inventory.loans.documents.reprint', [
            'document' => $document->id,
        ]), ['reason' => 'Salinan'])->assertStatus(409);

        $this->assertDatabaseCount('inventory_loan_document_prints', 0);
    }

    public function test_ba_correction_keeps_previous_pdf_and_issues_new_version(): void
    {
        $loan = $this->makeLoan(true);
        $service = app(InventoryLoanDocumentService::class);
        $first = $service->issue($loan->id, 'handover');
        $originalBytes = Storage::disk('local')->get($first->storage_path);

        $second = $service->correct($first->id, 'Koreksi administrasi rincian dokumen.');

        $this->assertSame(2, $second->version);
        $this->assertSame('issued', $second->status);
        $this->assertSame('superseded', $first->fresh()->status);
        $this->assertSame($originalBytes, Storage::disk('local')->get($first->storage_path));
        Storage::disk('local')->assertExists($second->storage_path);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($second->storage_path)), $second->sha256);
        $this->assertDatabaseCount('inventory_loan_documents', 2);
    }

    public function test_superseded_ba_cannot_be_corrected_again(): void
    {
        $loan = $this->makeLoan(true);
        $service = app(InventoryLoanDocumentService::class);
        $first = $service->issue($loan->id, 'handover');
        $service->correct($first->id, 'Koreksi administrasi rincian dokumen.');

        $this->expectException(ValidationException::class);
        $service->correct($first->id, 'Koreksi ulang terhadap dokumen lama.');
    }

    public function test_http_correction_creates_version_two_and_keeps_old_pdf_accessible(): void
    {
        $loan = $this->makeLoan(true);
        $first = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
        $bytes = Storage::disk('local')->get($first->storage_path);

        $response = $this->post(route('inventory.loans.documents.correct', [
            'document' => $first->id,
        ]), ['reason' => 'Perbaikan data administratif berita acara.']);

        $second = $loan->documents()->where('version', 2)->firstOrFail();
        $response->assertRedirect(route('inventory.loans.documents.show', ['document' => $second->id]));
        $this->assertSame('superseded', $first->fresh()->status);
        $this->get(route('inventory.loans.documents.show', ['document' => $first->id]))
            ->assertOk();
        $this->assertSame($bytes, Storage::disk('local')->get($first->storage_path));
    }

    public function test_http_correction_rejects_short_reason(): void
    {
        $loan = $this->makeLoan(true);
        $first = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');

        $this->post(route('inventory.loans.documents.correct', [
            'document' => $first->id,
        ]), ['reason' => 'singkat'])->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('inventory_loan_documents', 1);
    }

    public function test_reprint_permission_does_not_allow_correction(): void
    {
        $loan = $this->makeLoan(true);
        $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
        $schoolId = app(SchoolContext::class)->id();

        $member = User::factory()->create(['system_role' => null]);
        SchoolUserMembership::query()->create([
            'school_id' => $schoolId,
            'user_id' => $member->id,
            'is_active' => true,
        ]);
        setPermissionsTeamId($schoolId);
        Permission::findOrCreate('inventory.documents.reprint', 'web');
        $member->givePermissionTo('inventory.documents.reprint');
        $this->actingAs($member)->withSession(['active_school_id' => $schoolId]);

        $this->post(route('inventory.loans.documents.correct', [
            'document' => $document->id,
        ]), ['reason' => 'Perbaikan administrasi dokumen.'])->assertForbidden();

        $this->assertDatabaseCount('inventory_loan_documents', 1);
        $this->assertSame('issued', $document->fresh()->status);
    }

    public function test_correction_of_another_school_document_is_not_found(): void
    {
        $loan = $this->makeLoan(true);
        $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
        $otherSchool = School::factory()->create();
        app(SchoolContext::class)->set($otherSchool);
        $this->withSession(['active_school_id' => $otherSchool->id]);

        $this->post(route('inventory.loans.documents.correct', [
            'document' => $document->id,
        ]), ['reason' => 'Koreksi administrasi dokumen sekolah.'])->assertNotFound();

        $this->assertDatabaseCount('inventory_loan_documents', 1);
    }

    public function test_second_correction_has_stable_revision_number_and_full_history(): void
    {
        $loan = $this->makeLoan(true);
        $service = app(InventoryLoanDocumentService::class);
        $first = $service->issue($loan->id, 'handover');
        $second = $service->correct($first->id, 'Koreksi administratif pertama pada berita acara.');
        $third = $service->correct($second->id, 'Koreksi administratif kedua pada berita acara.');

        $this->assertSame(3, $third->version);
        $this->assertSame($first->document_number.'-REV3', $third->document_number);
        $this->assertSame('superseded', $first->fresh()->status);
        $this->assertSame('superseded', $second->fresh()->status);
        $this->assertSame('issued', $third->fresh()->status);
        $this->assertDatabaseCount('inventory_loan_documents', 3);
        Storage::disk('local')->assertExists($first->storage_path);
        Storage::disk('local')->assertExists($second->storage_path);
        Storage::disk('local')->assertExists($third->storage_path);
    }

    public function test_reprint_audit_records_the_operator_and_timestamp(): void
    {
        $loan = $this->makeLoan(true);
        $document = app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
        $operatorId = auth()->id();

        $this->post(route('inventory.loans.documents.reprint', [
            'document' => $document->id,
        ]), ['reason' => 'Penggantian salinan untuk arsip sekolah'])
            ->assertRedirect(route('inventory.loans.documents.show', ['document' => $document->id]));

        $print = $document->prints()->with('printer')->firstOrFail();
        $this->assertSame($operatorId, $print->printed_by);
        $this->assertSame($operatorId, $print->printer?->id);
        $this->assertNotNull($print->printed_at);
        $this->assertSame('reprint', $print->purpose);
    }

    private function makeLoan(bool $handover): InventoryLoan
    {
        $category = InventoryCategory::query()->create(['name' => 'Peralatan', 'is_active' => true]);
        $item = InventoryItem::query()->create([
            'category_id' => $category->id,
            'item_code' => 'BA-'.fake()->unique()->numerify('#####'),
            'name' => 'Tali Pramuka',
            'tracking_type' => 'quantity',
            'unit' => 'meter',
            'is_active' => true,
        ]);
        $owner = InventoryOwner::query()->create([
            'owner_type' => 'school', 'display_name' => 'Sekolah', 'is_active' => true,
        ]);
        $location = InventoryLocation::query()->create([
            'name' => 'Gudang', 'location_type' => 'storage', 'is_active' => true,
        ]);

        app(InventoryStockService::class)->recordMovement($item->id, $owner->id, $location->id, 'receipt', 10);

        $service = app(InventoryLoanService::class);
        $loan = $service->requestLoan(
            ['type' => 'manual_external', 'name' => 'Peminjam Uji'],
            [['item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id, 'quantity' => 2]],
        );

        if ($handover) {
            $service->approve($loan->id);
            $service->recordHandover($loan->id, [
                ['loan_item_id' => $loan->items->first()->id, 'quantity' => 2],
            ], now());
        }

        return $loan;
    }
}
