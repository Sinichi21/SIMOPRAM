<?php

use App\Livewire\Inventory\Loans;
use App\Models\InventoryAsset;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Services\Inventory\InventoryStockService;
use App\Support\SchoolContext;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function loanQuickEntryStock(): array
{
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    test()->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->withSession(['active_school_id' => $school->id]);
    $category = InventoryCategory::query()->create(['name' => 'Peralatan', 'is_active' => true]);
    $item = InventoryItem::query()->create([
        'category_id' => $category->id, 'item_code' => 'TALI-001', 'name' => 'Tali',
        'tracking_type' => 'quantity', 'unit' => 'meter', 'is_active' => true,
    ]);
    $owner = InventoryOwner::query()->create(['owner_type' => 'school', 'display_name' => 'Sekolah', 'is_active' => true]);
    $location = InventoryLocation::query()->create(['name' => 'Gudang', 'location_type' => 'storage', 'is_active' => true]);
    app(InventoryStockService::class)->recordMovement($item->id, $owner->id, $location->id, 'receipt', 5);

    return [$item, $owner, $location, $school];
}

it('selects items by code or existing QR and derives ownership and location', function (bool $qr) {
    [$item, $owner, $location, $school] = loanQuickEntryStock();
    $code = $qr ? route('inventory.items.verify', ['school' => $school->id, 'item' => $item->id]) : $item->item_code;

    Livewire::test(Loans::class)->set('showRequestForm', true)
        ->set('itemCode', $code)->call('findByCode')->assertSet('itemId', $item->id)
        ->set('quantity', '2.5')->call('addToCart')->assertHasNoErrors()
        ->assertSet('cart.0.owner_id', $owner->id)->assertSet('cart.0.location_id', $location->id)
        ->assertSet('cart.0.quantity', 2.5);
})->with([false, true]);

it('allocates multiple stock locations automatically without partial cart writes on shortage', function () {
    [$item, $owner, $location] = loanQuickEntryStock();
    $other = InventoryLocation::query()->create(['name' => 'Gudang 2', 'location_type' => 'storage', 'is_active' => true]);
    app(InventoryStockService::class)->recordMovement($item->id, $owner->id, $other->id, 'receipt', 3);

    $component = Livewire::test(Loans::class)->set('itemId', $item->id)->set('quantity', '9')
        ->call('addToCart')->assertHasErrors('quantity')->assertSet('cart', [])
        ->set('quantity', '7')->call('addToCart')->assertHasNoErrors()
        ->assertSet('cart.0.quantity', 5)->assertSet('cart.1.quantity', 2)
        ->assertSet('cart.1.location_id', $other->id);
    $component->set('itemId', $item->id)->set('quantity', '2')->call('addToCart')
        ->assertHasErrors('quantity')->assertSet('cart.1.quantity', 2);
});

it('resolves unit labels and refuses duplicate or unavailable units', function (bool $qr) {
    [$item, $owner, $location, $school] = loanQuickEntryStock();
    $item->update(['tracking_type' => 'individual']);
    $asset = InventoryAsset::query()->create([
        'item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id,
        'asset_code' => 'UNIT-001', 'condition' => 'good', 'status' => 'available',
    ]);
    $code = $qr ? route('inventory.assets.verify', ['school' => $school->id, 'asset' => $asset->id]) : $asset->asset_code;

    $component = Livewire::test(Loans::class)->set('itemCode', $code)->call('findByCode')
        ->assertSet('assetId', $asset->id)->call('addToCart')->assertHasNoErrors()
        ->assertSet('cart.0.asset_id', $asset->id)->assertSet('cart.0.location_id', $location->id)
        ->set('itemCode', $code)->call('findByCode')->call('addToCart')->assertHasErrors('quantity');
    $asset->update(['status' => 'borrowed']);
    $component->set('itemCode', $code)->call('findByCode')->assertHasErrors('itemCode')->assertSet('itemId', null);
})->with([false, true]);

it('rejects unknown and foreign school codes and clears the previously selected item', function () {
    [$item] = loanQuickEntryStock();
    $otherSchool = School::factory()->create();
    $foreignItem = $item->replicate();
    $foreignItem->school_id = $otherSchool->id;
    $foreignItem->item_code = 'FOREIGN';
    $foreignItem->save();

    $component = Livewire::test(Loans::class)->set('itemCode', $item->item_code)->call('findByCode');
    foreach (['UNKNOWN', 'FOREIGN', route('inventory.items.verify', ['school' => $otherSchool->id, 'item' => $foreignItem->id])] as $code) {
        $component->set('itemCode', $code)->call('findByCode')->assertHasErrors('itemCode')->assertSet('itemId', null);
    }
});

it('rejects fractional individual quantities and inactive stock owners', function () {
    [$item, $owner] = loanQuickEntryStock();
    $owner->update(['is_active' => false]);
    Livewire::test(Loans::class)->set('itemId', $item->id)->call('addToCart')
        ->assertHasErrors('quantity')->assertSet('cart', []);
    $item->update(['tracking_type' => 'individual']);
    Livewire::test(Loans::class)->set('itemId', $item->id)->set('quantity', '1.5')->call('addToCart')
        ->assertHasErrors('quantity')->assertSet('cart', []);
});

it('forbids code lookup without loan request permission', function () {
    [, , , $school] = loanQuickEntryStock();
    $member = User::factory()->create(['system_role' => null]);
    SchoolUserMembership::query()->create(['school_id' => $school->id, 'user_id' => $member->id, 'is_active' => true]);
    setPermissionsTeamId($school->id);
    Permission::findOrCreate('inventory.view', 'web');
    $member->givePermissionTo('inventory.view');
    test()->actingAs($member);

    Livewire::test(Loans::class)->set('itemCode', 'TALI-001')->call('findByCode')->assertForbidden();
});

it('persists a loan with automatically resolved stock references', function () {
    [$item, $owner, $location] = loanQuickEntryStock();

    Livewire::test(Loans::class)->set('itemCode', $item->item_code)->call('findByCode')
        ->set('quantity', '2')->call('addToCart')->set('borrowerName', 'Gudep Tamu')
        ->set('borrowerContact', '08123456789')->set('purpose', 'Latihan')
        ->call('requestLoan')->assertHasNoErrors()->assertSet('cart', []);

    test()->assertDatabaseHas('inventory_loan_items', [
        'item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id,
        'quantity_requested' => 2,
    ]);
    test()->assertDatabaseHas('inventory_stock_balances', ['item_id' => $item->id, 'quantity_on_hand' => 5]);
});
