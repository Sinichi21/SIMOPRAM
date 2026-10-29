<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\School;
use App\Models\User;
use App\Services\Inventory\InventoryLoanDocumentService;
use App\Services\Inventory\InventoryLoanService;
use App\Services\Inventory\InventoryStockService;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryLoanDocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SchoolContext::class)->set(School::factory()->create());
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
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
        try {
            app(InventoryLoanDocumentService::class)->issue($loan->id, 'handover');
        } finally {
            $this->assertDatabaseCount('inventory_loan_documents', 0);
        }
    }

    public function test_return_ba_cannot_be_issued_from_handover_events(): void
    {
        $loan = $this->makeLoan(true);

        $this->expectException(ValidationException::class);
        app(InventoryLoanDocumentService::class)->issue($loan->id, 'return');
    }

    private function makeLoan(bool $handover): \App\Models\InventoryLoan
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
