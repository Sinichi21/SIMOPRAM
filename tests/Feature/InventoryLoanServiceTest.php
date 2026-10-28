<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\School;
use App\Services\Inventory\InventoryLoanService;
use App\Services\Inventory\InventoryStockService;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryLoanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SchoolContext::class)->set(School::factory()->create());
    }

    public function test_loan_can_be_approved_handed_over_and_partially_returned(): void
    {
        [$item, $owner, $location] = $this->makeStock();
        $stock = app(InventoryStockService::class);
        $stock->recordMovement($item->id, $owner->id, $location->id, 'receipt', 10);

        $service = app(InventoryLoanService::class);
        $loan = $service->requestLoan(
            ['type' => 'manual_external', 'name' => 'Gudep Tamu'],
            [['item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id, 'quantity' => 6]],
        );
        $this->assertSame('requested', $loan->status);

        $service->approve($loan->id);
        $service->recordHandover($loan->id, [
            ['loan_item_id' => $loan->items->first()->id, 'quantity' => 6],
        ], now());
        $this->assertSame('handed_over', $loan->fresh()->status);
        $this->assertEquals(4, $item->stockBalances()->first()->quantity_on_hand);

        $service->recordReturn($loan->id, [
            ['loan_item_id' => $loan->items->first()->id, 'quantity_returned' => 2],
        ], now());
        $this->assertSame('partially_returned', $loan->fresh()->status);
        $this->assertEquals(6, $item->stockBalances()->first()->quantity_on_hand);

        $service->recordReturn($loan->id, [
            ['loan_item_id' => $loan->items->first()->id, 'quantity_returned' => 4],
        ], now());
        $this->assertSame('returned', $loan->fresh()->status);
        $this->assertEquals(10, $item->stockBalances()->first()->quantity_on_hand);
    }

    public function test_duplicate_stock_lines_cannot_exceed_available_balance(): void
    {
        [$item, $owner, $location] = $this->makeStock();
        app(InventoryStockService::class)->recordMovement($item->id, $owner->id, $location->id, 'receipt', 10);

        $line = ['item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id, 'quantity' => 7];

        try {
            app(InventoryLoanService::class)->requestLoan(
                ['type' => 'manual_external', 'name' => 'Peminjam'],
                [$line, $line],
            );
            $this->fail('Permohonan 14 unit dari stok 10 unit harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items.1.quantity', $exception->errors());
        }

        $this->assertDatabaseCount('inventory_loans', 0);
    }

    public function test_missing_quantity_is_recorded_as_discrepancy_without_restoring_stock(): void
    {
        [$item, $owner, $location] = $this->makeStock();
        app(InventoryStockService::class)->recordMovement($item->id, $owner->id, $location->id, 'receipt', 5);

        $service = app(InventoryLoanService::class);
        $loan = $service->requestLoan(
            ['type' => 'manual_external', 'name' => 'Peminjam'],
            [['item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id, 'quantity' => 3]],
        );
        $service->approve($loan->id);
        $lineId = $loan->items->first()->id;
        $service->recordHandover($loan->id, [['loan_item_id' => $lineId, 'quantity' => 3]], now());
        $service->recordReturn($loan->id, [
            ['loan_item_id' => $lineId, 'quantity_returned' => 2, 'quantity_missing' => 1],
        ], now(), 'offline');

        $this->assertSame('discrepancy', $loan->fresh()->status);
        $this->assertEquals(4, $item->stockBalances()->first()->quantity_on_hand);
        $this->assertDatabaseHas('inventory_loan_events', [
            'loan_id' => $loan->id,
            'loan_item_id' => $lineId,
            'event_type' => 'loss',
            'recording_method' => 'offline',
        ]);
    }

    public function test_approval_rejects_overlapping_stock_allocations(): void
    {
        [$item, $owner, $location] = $this->makeStock();
        app(InventoryStockService::class)->recordMovement($item->id, $owner->id, $location->id, 'receipt', 10);

        $service = app(InventoryLoanService::class);
        $borrower = ['type' => 'manual_external', 'name' => 'Peminjam'];
        $first = $service->requestLoan($borrower, [
            ['item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id, 'quantity' => 7],
        ]);
        $second = $service->requestLoan($borrower, [
            ['item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id, 'quantity' => 6],
        ]);

        $service->approve($first->id);

        try {
            $service->approve($second->id);
            $this->fail('Persetujuan kedua harus ditolak karena alokasi melebihi stok.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('requested', $second->fresh()->status);
    }

    private function makeStock(): array
    {
        $category = InventoryCategory::query()->create(['name' => 'Peralatan', 'is_active' => true]);
        $item = InventoryItem::query()->create([
            'category_id' => $category->id,
            'item_code' => 'LOAN-'.fake()->unique()->numerify('#####'),
            'name' => 'Tali',
            'tracking_type' => 'quantity',
            'unit' => 'meter',
            'is_active' => true,
        ]);
        $owner = InventoryOwner::query()->create([
            'owner_type' => 'school',
            'display_name' => 'Sekolah',
            'is_active' => true,
        ]);
        $location = InventoryLocation::query()->create([
            'name' => 'Gudang',
            'location_type' => 'storage',
            'is_active' => true,
        ]);

        return [$item, $owner, $location];
    }
}
