<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\InventoryStockBalance;
use App\Models\School;
use App\Services\Inventory\InventoryStockService;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryStockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
    }

    public function test_receipt_and_issue_update_the_balance_and_record_movements(): void
    {
        [$item, $owner, $location] = $this->makeQuantityStock();
        $service = app(InventoryStockService::class);

        $receipt = $service->recordMovement($item->id, $owner->id, $location->id, 'receipt', 12);
        $issue = $service->recordMovement($item->id, $owner->id, $location->id, 'issue', 3);

        $this->assertSame('12.000', $receipt->balance_after);
        $this->assertSame('9.000', $issue->balance_after);
        $this->assertSame(2, $item->stockBalances()->count());
        $this->assertDatabaseHas('inventory_stock_balances', [
            'item_id' => $item->id,
            'owner_id' => $owner->id,
            'location_id' => $location->id,
            'quantity_on_hand' => 9,
        ]);
    }

    public function test_issue_cannot_make_stock_negative(): void
    {
        [$item, $owner, $location] = $this->makeQuantityStock();
        $service = app(InventoryStockService::class);

        try {
            $service->recordMovement($item->id, $owner->id, $location->id, 'issue', 1);
            $this->fail('Pengeluaran melebihi stok seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }

        $this->assertDatabaseMissing('inventory_stock_movements', [
            'item_id' => $item->id,
            'movement_type' => 'issue',
        ]);
    }

    public function test_adjustment_records_the_difference_not_the_final_balance_as_movement_quantity(): void
    {
        [$item, $owner, $location] = $this->makeQuantityStock();
        $service = app(InventoryStockService::class);

        $service->recordMovement($item->id, $owner->id, $location->id, 'receipt', 8);
        $adjustment = $service->recordMovement($item->id, $owner->id, $location->id, 'adjustment', 6);

        $this->assertSame('2.000', $adjustment->quantity);
        $this->assertSame('6.000', $adjustment->balance_after);
    }

    public function test_individual_asset_item_cannot_use_quantity_stock_service(): void
    {
        [$item, $owner, $location] = $this->makeQuantityStock();
        $item->update(['tracking_type' => 'individual']);

        $this->expectException(ValidationException::class);

        app(InventoryStockService::class)->recordMovement($item->id, $owner->id, $location->id, 'receipt', 1);
    }

    /** @return array{InventoryItem, InventoryOwner, InventoryLocation} */
    private function makeQuantityStock(): array
    {
        $category = InventoryCategory::query()->create([
            'name' => 'Peralatan',
            'description' => 'Kategori pengujian',
            'is_active' => true,
        ]);

        $item = InventoryItem::query()->create([
            'category_id' => $category->id,
            'item_code' => 'TEST-ITEM-'.fake()->unique()->numerify('#####'),
            'name' => 'Tali Pramuka',
            'tracking_type' => 'quantity',
            'unit' => 'meter',
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        $owner = InventoryOwner::query()->create([
            'owner_type' => 'school',
            'display_name' => 'Inventaris Sekolah',
            'is_active' => true,
        ]);

        $location = InventoryLocation::query()->create([
            'name' => 'Gudang Utama',
            'location_type' => 'storage',
            'is_active' => true,
        ]);

        return [$item, $owner, $location];
    }
}
