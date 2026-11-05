<?php

namespace Tests\Feature;

use App\Livewire\Administration\GudepFinance;
use App\Models\AcademicYear;
use App\Models\GudepCashEntry;
use App\Models\GudepCashInventoryCorrection;
use App\Models\GudepCashInventoryReceipt;
use App\Models\GudepCashInventoryReceiptItem;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\InventoryStockBalance;
use App\Models\InventoryStockMovement;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GudepCashInventoryReceiptTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{School, ScoutGroup, AcademicYear, GudepCashEntry, InventoryItem, InventoryOwner, InventoryLocation} */
    private function context(): array
    {
        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        setPermissionsTeamId($school->id);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);

        $gudep = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Stok']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id, 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
        ]);
        $entry = GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'direction' => 'expense',
            'category' => 'ATK',
            'occurred_at' => '2026-11-05',
            'description' => 'Pembelian tali',
            'amount' => 50000,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        $category = InventoryCategory::query()->create([
            'school_id' => $school->id, 'name' => 'Peralatan', 'is_active' => true,
        ]);
        $item = InventoryItem::query()->create([
            'school_id' => $school->id,
            'category_id' => $category->id,
            'item_code' => 'TALI-01',
            'name' => 'Tali',
            'tracking_type' => 'quantity',
            'unit' => 'gulungan',
            'is_active' => true,
        ]);
        $owner = InventoryOwner::query()->create([
            'school_id' => $school->id,
            'owner_type' => 'gudep',
            'scout_group_id' => $gudep->id,
            'display_name' => 'Gudep Stok',
            'is_active' => true,
        ]);
        $location = InventoryLocation::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'name' => 'Gudang Gudep',
            'is_active' => true,
        ]);

        return [$school, $gudep, $year, $entry, $item, $owner, $location];
    }

    public function test_new_purchase_can_reference_catalog_item_before_it_has_stock(): void
    {
        [, $gudep, $year, $existingEntry, $item] = $this->context();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('cashDirection', 'expense')
            ->set('cashCategory', 'ATK')
            ->set('cashDate', '2026-11-05')
            ->set('cashDescription', 'Tambahan pembelian tali')
            ->set('cashAmount', '25000')
            ->set('cashInventoryItemId', $item->id)
            ->call('saveCash')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gudep_cash_entries', [
            'description' => 'Tambahan pembelian tali',
            'inventory_item_id' => $item->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseCount('inventory_stock_balances', 0);
        $this->assertSame(50000, $existingEntry->fresh()->amount);
    }

    public function test_posted_expense_adds_stock_only_after_explicit_receipt(): void
    {
        [, $gudep, $year, $entry, $item, $owner, $location] = $this->context();

        $component = Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->assertViewHas('expense', 50000)
            ->call('selectCashForInventoryReceipt', $entry->id)
            ->assertSet('receivingCashEntryId', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '3')
            ->call('recordCashInventoryReceipt')
            ->assertHasNoErrors()
            ->assertViewHas('expense', 50000);

        $this->assertSame(1, InventoryStockMovement::query()->count());
        $this->assertSame(1, GudepCashInventoryReceipt::query()->count());
        $this->assertSame('3.000', InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);
        $this->assertSame('KAS-'.$entry->id, InventoryStockMovement::query()->firstOrFail()->reference);
        $this->assertSame(50000, $entry->fresh()->amount);

        $component->assertSet('receivingCashEntryId', null);
    }

    public function test_receipt_cannot_be_recorded_twice_for_same_expense(): void
    {
        [, $gudep, $year, $entry, $item, $owner, $location] = $this->context();

        $record = fn () => Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receivingCashEntryId', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '2')
            ->call('recordCashInventoryReceipt');

        $record()->assertHasNoErrors();
        $record()->assertStatus(409);

        $this->assertSame(1, InventoryStockMovement::query()->count());
        $this->assertSame('2.000', InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);
    }

    public function test_expense_cannot_use_another_gudep_owner(): void
    {
        [$school, $gudep, $year, $entry, $item, , $location] = $this->context();
        $other = ScoutGroup::query()->create(['school_id' => $school->id, 'name' => 'Gudep Lain']);
        $foreignOwner = InventoryOwner::query()->create([
            'school_id' => $school->id,
            'owner_type' => 'gudep',
            'scout_group_id' => $other->id,
            'display_name' => 'Gudep Lain',
            'is_active' => true,
        ]);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receivingCashEntryId', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $foreignOwner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '1')
            ->call('recordCashInventoryReceipt')
            ->assertForbidden();

        $this->assertDatabaseCount('inventory_stock_movements', 0);
    }

    public function test_expense_with_received_stock_cannot_be_reversed_without_inventory_correction(): void
    {
        [, $gudep, $year, $entry, $item, $owner, $location] = $this->context();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receivingCashEntryId', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '1')
            ->call('recordCashInventoryReceipt')
            ->assertHasNoErrors();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForReversal', $entry->id)
            ->assertStatus(409);

        $this->assertDatabaseCount('gudep_cash_entries', 1);
        $this->assertDatabaseCount('inventory_stock_movements', 1);
    }

    public function test_multiple_items_are_received_atomically_without_changing_cash_amount(): void
    {
        [$school, $gudep, $year, $entry, $item, $owner, $location] = $this->context();
        $other = InventoryItem::query()->create([
            'school_id' => $school->id,
            'category_id' => $item->category_id,
            'item_code' => 'KERTAS-01',
            'name' => 'Kertas',
            'tracking_type' => 'quantity',
            'unit' => 'rim',
            'is_active' => true,
        ]);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForInventoryReceipt', $entry->id)
            ->call('addReceivingItem')
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '3')
            ->set('receivingExtraItems.0.item_id', (string) $other->id)
            ->set('receivingExtraItems.0.owner_id', (string) $owner->id)
            ->set('receivingExtraItems.0.location_id', (string) $location->id)
            ->set('receivingExtraItems.0.quantity', '2')
            ->call('recordCashInventoryReceipt')
            ->assertHasNoErrors()
            ->assertViewHas('expense', 50000);

        $this->assertDatabaseCount('gudep_cash_inventory_receipts', 1);
        $this->assertDatabaseCount('gudep_cash_inventory_receipt_items', 1);
        $this->assertDatabaseCount('inventory_stock_movements', 2);
        $this->assertSame(50000, $entry->fresh()->amount);
        $this->assertSame(2, InventoryStockBalance::query()->count());
        $this->assertSame(1, GudepCashInventoryReceiptItem::query()->count());
        $this->assertSame('KAS-'.$entry->id, InventoryStockMovement::query()->orderBy('id')->firstOrFail()->reference);
        $this->assertSame('KAS-'.$entry->id.'-2', InventoryStockMovement::query()->orderByDesc('id')->firstOrFail()->reference);
    }

    public function test_invalid_additional_item_does_not_create_any_stock_movement(): void
    {
        [, $gudep, $year, $entry, $item, $owner, $location] = $this->context();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receivingCashEntryId', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '3')
            ->set('receivingExtraItems', [[
                'item_id' => '999999',
                'owner_id' => (string) $owner->id,
                'location_id' => (string) $location->id,
                'quantity' => '1',
            ]])
            ->call('recordCashInventoryReceipt')
            ->assertHasErrors(['receivingExtraItems.0.item_id']);

        $this->assertDatabaseCount('gudep_cash_inventory_receipts', 0);
        $this->assertDatabaseCount('gudep_cash_inventory_receipt_items', 0);
        $this->assertDatabaseCount('inventory_stock_movements', 0);
        $this->assertDatabaseCount('inventory_stock_balances', 0);
    }

    public function test_service_failure_on_second_item_rolls_back_first_receipt(): void
    {
        [, $gudep, $year, $entry, $item, $owner, $location] = $this->context();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receivingCashEntryId', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '2')
            ->set('receivingExtraItems', [[
                'item_id' => (string) $item->id,
                'owner_id' => (string) $owner->id,
                'location_id' => (string) $location->id,
                'quantity' => '0.1234',
            ]])
            ->call('recordCashInventoryReceipt')
            ->assertHasErrors(['quantity']);

        $this->assertDatabaseCount('inventory_stock_movements', 0);
        $this->assertDatabaseCount('inventory_stock_balances', 0);
        $this->assertDatabaseCount('gudep_cash_inventory_receipts', 0);
        $this->assertDatabaseCount('gudep_cash_inventory_receipt_items', 0);
    }

    public function test_additional_item_rows_can_be_added_and_removed_without_stock_changes(): void
    {
        [, $gudep, $year, $entry] = $this->context();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForInventoryReceipt', $entry->id)
            ->call('addReceivingItem')
            ->assertCount('receivingExtraItems', 1)
            ->call('removeReceivingItem', 0)
            ->assertCount('receivingExtraItems', 0);

        $this->assertDatabaseCount('inventory_stock_movements', 0);
    }

    public function test_partial_receipt_can_follow_first_delivery_and_keep_original_cash(): void
    {
        [, $gudep, $year, $entry, $item, $owner, $location] = $this->context();
        $newReceipt = fn () => Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForInventoryReceipt', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id);

        $newReceipt()->set('receivingQuantity', '2')
            ->call('recordCashInventoryReceipt')->assertHasNoErrors();

        $newReceipt()->set('receivingQuantity', '3')
            ->call('recordCashInventoryReceipt')->assertHasNoErrors()
            ->assertViewHas('expense', 50000);

        $this->assertDatabaseCount('gudep_cash_inventory_receipts', 1);
        $this->assertDatabaseCount('gudep_cash_inventory_receipt_items', 1);
        $this->assertDatabaseCount('inventory_stock_movements', 2);
        $this->assertSame('5.000', InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);
        $this->assertSame(50000, $entry->fresh()->amount);
    }

    public function test_replayed_partial_receipt_token_does_not_duplicate_stock(): void
    {
        [, $gudep, $year, $entry, $item, $owner, $location] = $this->context();
        $first = Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectCashForInventoryReceipt', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '2');
        $firstToken = $first->get('receivingBatchToken');
        $first->call('recordCashInventoryReceipt')->assertHasNoErrors();

        $replay = fn (string $token) => Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receivingCashEntryId', $entry->id)
            ->set('receivingBatchToken', $token)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '2')
            ->call('recordCashInventoryReceipt');

        $replay($firstToken)->assertStatus(409);

        $partialToken = '550e8400-e29b-41d4-a716-446655440000';
        $replay($partialToken)->assertHasNoErrors();
        $replay($partialToken)->assertStatus(409);

        $this->assertDatabaseCount('inventory_stock_movements', 2);
        $this->assertSame('4.000', InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);
    }

    public function test_receipt_correction_creates_issue_and_prevents_excessive_reversal(): void
    {
        [, $gudep, $year, $entry, $item, $owner, $location] = $this->context();
        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receivingCashEntryId', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '3')
            ->call('recordCashInventoryReceipt')->assertHasNoErrors();

        $originalMovementId = InventoryStockMovement::query()->firstOrFail()->id;
        $correct = fn (string $quantity) => Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectInventoryReceiptCorrection', $entry->id, $originalMovementId)
            ->set('correctionQuantity', $quantity)
            ->set('correctionReason', 'Salah input jumlah penerimaan barang.')
            ->call('correctInventoryReceipt');

        $correct('1')->assertHasNoErrors();
        $correct('3')->assertStatus(409);
        $correct('2')->assertHasNoErrors();

        $this->assertDatabaseCount('gudep_cash_inventory_corrections', 2);
        $this->assertDatabaseCount('inventory_stock_movements', 3);
        $this->assertSame('0.000', InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);
        $this->assertSame(50000, $entry->fresh()->amount);
        $this->assertSame(2, GudepCashInventoryCorrection::query()->count());
    }

    public function test_stock_correction_rejects_movement_from_unrelated_cash_receipt(): void
    {
        [$school, $gudep, $year, $entry, $item, $owner, $location] = $this->context();
        $other = GudepCashEntry::query()->create([
            'school_id' => $school->id,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'direction' => 'expense',
            'category' => 'ATK',
            'occurred_at' => '2026-11-05',
            'description' => 'Pembelian barang lain',
            'amount' => 10000,
            'status' => 'posted',
        ]);

        foreach ([$entry, $other] as $e) {
            Livewire::test(GudepFinance::class)
                ->set('scoutGroupId', $gudep->id)
                ->set('academicYearId', $year->id)
                ->set('receivingCashEntryId', $e->id)
                ->set('receivingItemId', $item->id)
                ->set('receivingOwnerId', $owner->id)
                ->set('receivingLocationId', $location->id)
                ->set('receivingQuantity', '1')
                ->call('recordCashInventoryReceipt')->assertHasNoErrors();
        }

        $otherMovement = GudepCashInventoryReceipt::query()
            ->where('gudep_cash_entry_id', $other->id)->firstOrFail()->inventory_stock_movement_id;

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('correctionCashEntryId', $entry->id)
            ->set('correctionMovementId', $otherMovement)
            ->set('correctionQuantity', '1')
            ->set('correctionReason', 'Tidak terkait dengan transaksi ini.')
            ->call('correctInventoryReceipt')
            ->assertNotFound();

        $this->assertDatabaseCount('gudep_cash_inventory_corrections', 0);
    }

    public function test_sarana_history_shows_received_items_and_correction_without_new_cash_expense(): void
    {
        [, $gudep, $year, $entry, $item, $owner, $location] = $this->context();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receivingCashEntryId', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '3')
            ->call('recordCashInventoryReceipt')
            ->assertHasNoErrors();

        $movementId = InventoryStockMovement::query()->firstOrFail()->id;
        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->call('selectInventoryReceiptCorrection', $entry->id, $movementId)
            ->set('correctionQuantity', '1')
            ->set('correctionReason', 'Jumlah penerimaan sebelumnya salah.')
            ->call('correctInventoryReceipt')
            ->assertHasNoErrors();

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->assertSee('Riwayat penerimaan yang terhubung ke Buku Kas')
            ->assertSee('Tali')
            ->assertSee('Jumlah penerimaan sebelumnya salah.')
            ->assertViewHas('expense', 50000);

        $this->assertDatabaseCount('gudep_cash_entries', 1);
        $this->assertSame('2.000', InventoryStockBalance::query()->firstOrFail()->quantity_on_hand);
    }

    public function test_draft_cannot_generate_inventory_receipt(): void
    {
        [, $gudep, $year, $entry, $item, $owner, $location] = $this->context();
        $entry->update(['status' => 'draft', 'posted_at' => null]);

        Livewire::test(GudepFinance::class)
            ->set('scoutGroupId', $gudep->id)
            ->set('academicYearId', $year->id)
            ->set('receivingCashEntryId', $entry->id)
            ->set('receivingItemId', $item->id)
            ->set('receivingOwnerId', $owner->id)
            ->set('receivingLocationId', $location->id)
            ->set('receivingQuantity', '1')
            ->call('recordCashInventoryReceipt')
            ->assertStatus(409);

        $this->assertDatabaseCount('inventory_stock_movements', 0);
    }
}
