<?php

use App\Livewire\Inventory\Index;
use App\Livewire\Inventory\Loans;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\School;
use App\Models\User;
use App\Services\Inventory\InventoryLoanDocumentService;
use App\Services\Inventory\InventoryLoanService;
use App\Services\Inventory\InventoryStockService;
use App\Support\SchoolContext;
use Livewire\Livewire;

function inventoryDisplayLoan(): InventoryLoan
{
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    test()->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->withSession(['active_school_id' => $school->id]);
    $category = InventoryCategory::query()->create(['name' => 'Peralatan', 'is_active' => true]);
    $item = InventoryItem::query()->create([
        'category_id' => $category->id, 'item_code' => 'FORMAT-001', 'name' => 'Tali',
        'tracking_type' => 'quantity', 'unit' => 'meter', 'is_active' => true,
    ]);
    $owner = InventoryOwner::query()->create(['owner_type' => 'school', 'display_name' => 'Sekolah', 'is_active' => true]);
    $location = InventoryLocation::query()->create(['name' => 'Gudang', 'location_type' => 'storage', 'is_active' => true]);
    app(InventoryStockService::class)->recordMovement($item->id, $owner->id, $location->id, 'receipt', 1234);
    $service = app(InventoryLoanService::class);
    $loan = $service->requestLoan(['type' => 'manual_external', 'name' => 'Peminjam'], [
        ['item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id, 'quantity' => 12],
    ]);
    $service->approve($loan->id);
    $lineId = $loan->items->first()->id;
    $service->recordHandover($loan->id, [['loan_item_id' => $lineId, 'quantity' => 12]], now());
    $service->recordReturn($loan->id, [['loan_item_id' => $lineId, 'quantity_returned' => 2]], now());

    return $loan;
}

it('displays inventory stock and movements without decimal or thousands separators', function () {
    inventoryDisplayLoan();

    Livewire::test(Index::class)->assertSee('1224 meter')
        ->assertSee('1234')->assertDontSee('1,234.000')->assertDontSee('1,224.000');
});

it('displays loan totals cart quantities and transaction choices as whole numbers', function () {
    $loan = inventoryDisplayLoan();

    Livewire::test(Loans::class)->set('showRequestForm', true)->set('itemId', $loan->items->first()->item_id)
        ->set('quantity', '4')->call('addToCart')->assertSee('4 unit')->assertDontSee('4.000')
        ->call('openTransaction', $loan->id, 'return')
        ->assertSee('diminta 12, diserahkan 12, kembali 2')->assertDontSee('12.000')->assertDontSee('2.000');
});

it('renders whole number quantities in handover and return BA', function (string $type, string $quantity) {
    $loan = inventoryDisplayLoan();
    $data = app(InventoryLoanDocumentService::class)->previewData($loan, $type);

    $html = view('inventory.documents.preview', $data)->render();

    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $cells = $xpath->query('//table[@class="transactions"]/tbody/tr/td[6]');
    expect($cells->length)->toBe(1);
    expect(trim($cells->item(0)->textContent))->toBe($quantity);
})->with([['handover', '12'], ['return', '2']]);
