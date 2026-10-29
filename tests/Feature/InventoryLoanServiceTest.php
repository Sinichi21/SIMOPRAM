<?php

namespace Tests\Feature;

use App\Models\InventoryAsset;
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

    public function test_approval_rejects_individual_asset_that_became_damaged(): void
    {
        [$item, $owner, $location, $asset] = $this->makeIndividualAsset();
        $service = app(InventoryLoanService::class);
        $loan = $service->requestLoan(
            ['type' => 'manual_external', 'name' => 'Peminjam'],
            [[
                'item_id' => $item->id, 'owner_id' => $owner->id,
                'location_id' => $location->id, 'asset_id' => $asset->id, 'quantity' => 1,
            ]],
        );
        $asset->forceFill(['condition' => 'damaged'])->save();

        try {
            $service->approve($loan->id);
            $this->fail('Aset rusak tidak boleh disetujui.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertSame('requested', $loan->fresh()->status);
    }

    public function test_handover_rejects_individual_asset_that_changed_location(): void
    {
        [$item, $owner, $location, $asset] = $this->makeIndividualAsset();
        $service = app(InventoryLoanService::class);
        $loan = $service->requestLoan(
            ['type' => 'manual_external', 'name' => 'Peminjam'],
            [[
                'item_id' => $item->id, 'owner_id' => $owner->id,
                'location_id' => $location->id, 'asset_id' => $asset->id, 'quantity' => 1,
            ]],
        );
        $service->approve($loan->id);
        $otherLocation = InventoryLocation::query()->create([
            'name' => 'Ruang penyimpanan kedua', 'location_type' => 'room', 'is_active' => true,
        ]);
        $asset->forceFill(['location_id' => $otherLocation->id])->save();

        try {
            $service->recordHandover($loan->id, [
                ['loan_item_id' => $loan->items->first()->id, 'quantity' => 1],
            ], now());
            $this->fail('Aset yang berpindah lokasi tidak boleh diserahterimakan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('lines.0.asset_id', $exception->errors());
        }

        $this->assertSame('available', $asset->fresh()->status);
        $this->assertSame('approved', $loan->fresh()->status);
        $this->assertDatabaseCount('inventory_loan_events', 0);
    }

    public function test_individual_asset_handover_and_good_return_create_audit_history(): void
    {
        [$item, $owner, $location, $asset] = $this->makeIndividualAsset();
        $service = app(InventoryLoanService::class);
        $loan = $service->requestLoan(
            ['type' => 'manual_external', 'name' => 'Peminjam'],
            [[
                'item_id' => $item->id, 'owner_id' => $owner->id,
                'location_id' => $location->id, 'asset_id' => $asset->id, 'quantity' => 1,
            ]],
        );
        $service->approve($loan->id);
        $lineId = $loan->items->first()->id;

        $service->recordHandover($loan->id, [['loan_item_id' => $lineId, 'quantity' => 1]], now());
        $this->assertSame('on_loan', $asset->fresh()->status);
        $this->assertDatabaseHas('inventory_asset_events', [
            'asset_id' => $asset->id,
            'from_location_id' => $location->id,
            'to_location_id' => null,
            'from_condition' => 'good',
            'to_condition' => 'good',
        ]);

        $service->recordReturn($loan->id, [
            ['loan_item_id' => $lineId, 'quantity_returned' => 1, 'condition' => 'good'],
        ], now());
        $this->assertSame('available', $asset->fresh()->status);
        $this->assertSame($location->id, $asset->fresh()->location_id);
        $this->assertDatabaseHas('inventory_asset_events', [
            'asset_id' => $asset->id,
            'from_location_id' => null,
            'to_location_id' => $location->id,
            'to_condition' => 'good',
        ]);
        $this->assertDatabaseCount('inventory_asset_events', 2);
    }

    public function test_individual_asset_loss_creates_audit_and_marks_missing(): void
    {
        [$item, $owner, $location, $asset] = $this->makeIndividualAsset();
        $service = app(InventoryLoanService::class);
        $loan = $service->requestLoan(
            ['type' => 'manual_external', 'name' => 'Peminjam'],
            [[
                'item_id' => $item->id, 'owner_id' => $owner->id,
                'location_id' => $location->id, 'asset_id' => $asset->id, 'quantity' => 1,
            ]],
        );
        $service->approve($loan->id);
        $lineId = $loan->items->first()->id;
        $service->recordHandover($loan->id, [['loan_item_id' => $lineId, 'quantity' => 1]], now());
        $service->recordReturn($loan->id, [
            ['loan_item_id' => $lineId, 'quantity_returned' => 0, 'quantity_missing' => 1],
        ], now());

        $this->assertSame('missing', $asset->fresh()->status);
        $this->assertSame('missing', $asset->fresh()->condition);
        $this->assertSame('discrepancy', $loan->fresh()->status);
        $this->assertDatabaseHas('inventory_asset_events', [
            'asset_id' => $asset->id,
            'from_location_id' => null,
            'to_location_id' => null,
            'to_condition' => 'missing',
        ]);
        $this->assertDatabaseCount('inventory_asset_events', 2);
    }

    private function makeIndividualAsset(): array
    {
        [$item, $owner, $location] = $this->makeStock();
        $item->forceFill(['tracking_type' => 'individual'])->save();
        $asset = InventoryAsset::query()->create([
            'item_id' => $item->id, 'owner_id' => $owner->id,
            'location_id' => $location->id, 'asset_code' => 'ASSET-'.fake()->unique()->numerify('#####'),
            'condition' => 'good', 'status' => 'available',
        ]);

        return [$item, $owner, $location, $asset];
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
