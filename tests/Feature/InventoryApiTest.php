<?php

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Services\Inventory\InventoryStockService;
use App\Support\SchoolContext;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

function inventoryApiFixture(): array
{
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create(['system_role' => 'super_admin']);
    Sanctum::actingAs($user);
    test()->withHeader('X-School-Id', (string) $school->id);
    $category = InventoryCategory::query()->create(['name' => 'Peralatan', 'is_active' => true]);
    $item = InventoryItem::query()->create([
        'category_id' => $category->id, 'item_code' => 'TALI-API', 'name' => 'Tali',
        'tracking_type' => 'quantity', 'unit' => 'meter', 'is_active' => true,
    ]);
    $owner = InventoryOwner::query()->create(['owner_type' => 'school', 'display_name' => 'Sekolah', 'is_active' => true]);
    $location = InventoryLocation::query()->create(['name' => 'Gudang', 'location_type' => 'storage', 'is_active' => true]);
    app(InventoryStockService::class)->recordMovement($item->id, $owner->id, $location->id, 'receipt', 20);

    return compact('school', 'user', 'item', 'owner', 'location', 'category');
}

function inventoryApiLoanPayload(int $itemId, int $quantity = 3): array
{
    return ['borrower' => ['type' => 'manual_external', 'name' => 'Tamu', 'contact' => '08123456789'],
        'purpose' => 'Latihan', 'items' => [['item_id' => $itemId, 'quantity' => $quantity]]];
}

it('rejects unauthenticated inventory and auth context requests', function () {
    test()->getJson('/api/v1/auth/me')->assertUnauthorized();
    test()->getJson('/api/v1/inventory/items')->assertUnauthorized();
});

it('returns JSON for inactive accounts and refuses inventory users without permission', function () {
    $data = inventoryApiFixture();
    $member = User::factory()->create(['system_role' => null]);
    SchoolUserMembership::query()->create(['user_id' => $member->id, 'school_id' => $data['school']->id, 'is_active' => true]);
    Sanctum::actingAs($member);
    test()->getJson('/api/v1/inventory/items')->assertForbidden();
    $member->update(['is_active' => false]);
    test()->getJson('/api/v1/inventory/items')->assertForbidden()->assertJsonStructure(['message']);
});

it('lists permitted schools and requires the school header for inventory', function () {
    $school = School::factory()->create();
    Sanctum::actingAs(User::factory()->create(['system_role' => 'super_admin']));
    test()->getJson('/api/v1/inventory/context')->assertUnprocessable();
    test()->getJson('/api/v1/schools')->assertJsonFragment(['id' => $school->id, 'name' => $school->name]);
    test()->withHeader('X-School-Id', (string) $school->id)->getJson('/api/v1/inventory/context')
        ->assertJsonPath('data.school_id', $school->id)->assertJsonPath('data.authentication', 'sanctum_bearer');
    expect(app(SchoolContext::class)->id())->toBeNull();
});

it('does not let a school member request a foreign school', function () {
    $data = inventoryApiFixture();
    $other = School::factory()->create();
    $member = User::factory()->create(['system_role' => null]);
    SchoolUserMembership::query()->create(['user_id' => $member->id, 'school_id' => $data['school']->id, 'is_active' => true]);
    Sanctum::actingAs($member);
    test()->withHeader('X-School-Id', (string) $other->id)->getJson('/api/v1/inventory/context')->assertForbidden();
    test()->getJson('/api/v1/schools')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $data['school']->id);
});

it('lists searches and paginates catalog items with numeric quantities', function () {
    $data = inventoryApiFixture();

    test()->getJson('/api/v1/inventory/items?search=TALI&per_page=1')->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $data['item']->id)
        ->assertJsonPath('data.0.total_quantity', 20)->assertJsonStructure(['links', 'meta']);
    test()->getJson('/api/v1/inventory/items?per_page=101')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    test()->getJson('/api/v1/inventory/items/'.$data['item']->id)->assertJsonPath('data.stock_balances.0.quantity_on_hand', 20);
});

it('creates updates and deactivates master records without accepting tenant or audit fields', function (string $catalog, array $payload) {
    $data = inventoryApiFixture();
    $payload['school_id'] = 99999;

    $id = test()->postJson('/api/v1/inventory/'.$catalog, $payload)->assertCreated()->json('data.id');
    test()->patchJson('/api/v1/inventory/'.$catalog.'/'.$id, ['is_active' => false])->assertOk()->assertJsonPath('data.is_active', false);

    test()->assertDatabaseHas('inventory_'.$catalog, ['id' => $id, 'school_id' => $data['school']->id, 'is_active' => false]);
})->with([
    ['categories', ['name' => 'Tenda']],
    ['owners', ['display_name' => 'Gudang Sekolah', 'owner_type' => 'school']],
    ['locations', ['name' => 'Ruang Baru', 'location_type' => 'room']],
]);

it('validates item creation and protects used item tracking types', function () {
    $data = inventoryApiFixture();
    test()->postJson('/api/v1/inventory/items', ['name' => 'Incomplete'])->assertUnprocessable()->assertJsonValidationErrors(['item_code', 'category_id', 'tracking_type']);
    test()->postJson('/api/v1/inventory/items', ['category_id' => $data['category']->id, 'name' => 'Kompas', 'item_code' => 'KOMPAS', 'unit' => 'unit', 'tracking_type' => 'individual'])
        ->assertCreated()->assertJsonPath('data.item_code', 'KOMPAS');
    test()->patchJson('/api/v1/inventory/items/'.$data['item']->id, ['name' => 'Tali Baru'])->assertOk()->assertJsonPath('data.total_quantity', 20);
    test()->patchJson('/api/v1/inventory/items/'.$data['item']->id, ['tracking_type' => 'individual'])->assertUnprocessable();
});

it('rejects all catalog and stock references from another school', function () {
    $data = inventoryApiFixture();
    $other = School::factory()->create();
    app(SchoolContext::class)->set($other);
    $category = InventoryCategory::query()->create(['name' => 'Foreign', 'is_active' => true]);
    $item = InventoryItem::query()->create(['category_id' => $category->id, 'item_code' => 'FOREIGN', 'name' => 'Foreign', 'tracking_type' => 'quantity', 'unit' => 'unit', 'is_active' => true]);
    app(SchoolContext::class)->set($data['school']);

    test()->getJson('/api/v1/inventory/items/'.$item->id)->assertNotFound();
    test()->getJson('/api/v1/inventory/items')->assertJsonCount(1, 'data');
    test()->postJson('/api/v1/inventory/items', ['category_id' => $category->id, 'item_code' => 'BAD', 'name' => 'Bad', 'unit' => 'unit', 'tracking_type' => 'quantity'])->assertUnprocessable();
    test()->postJson('/api/v1/inventory/loans', inventoryApiLoanPayload($item->id))->assertUnprocessable();
});

it('resolves item and unit QR payloads and refuses foreign QR', function () {
    $data = inventoryApiFixture();
    $qr = route('inventory.items.verify', ['school' => $data['school']->id, 'item' => $data['item']->id]);

    foreach ([$qr, $data['item']->item_code] as $code) {
        test()->getJson('/api/v1/inventory/resolve?'.http_build_query(['code' => $code]))->assertOk()->assertJsonPath('data.item.id', $data['item']->id);
    }
    test()->getJson('/api/v1/inventory/resolve?code=UNKNOWN')->assertUnprocessable()->assertJsonValidationErrors('code');
    test()->getJson('/api/v1/inventory/resolve?'.http_build_query(['code' => route('inventory.items.verify', ['school' => 99999, 'item' => $data['item']->id])]))->assertUnprocessable();
});

it('records stock movements and rejects insufficient stock without changing the balance', function () {
    $data = inventoryApiFixture();
    $payload = ['item_id' => $data['item']->id, 'owner_id' => $data['owner']->id, 'location_id' => $data['location']->id, 'movement_type' => 'issue', 'quantity' => 3];

    test()->postJson('/api/v1/inventory/stock-movements', $payload)->assertCreated()->assertJsonPath('data.balance_after', 17);
    test()->postJson('/api/v1/inventory/stock-movements', array_merge($payload, ['quantity' => 99]))->assertUnprocessable();
    test()->getJson('/api/v1/inventory/stock-balances')->assertJsonPath('data.0.quantity_on_hand', 17);
    test()->getJson('/api/v1/inventory/stock-movements')->assertJsonCount(2, 'data');
});

it('registers numbered units and audits condition changes through the shared asset service', function () {
    $data = inventoryApiFixture();
    $item = InventoryItem::query()->create(['category_id' => $data['category']->id, 'item_code' => 'UNIT', 'name' => 'Kompas', 'tracking_type' => 'individual', 'unit' => 'unit', 'is_active' => true]);
    $response = test()->postJson('/api/v1/inventory/assets', ['item_id' => $item->id, 'owner_id' => $data['owner']->id, 'location_id' => $data['location']->id, 'code_prefix' => 'UNIT', 'quantity' => 2]);
    $response->assertCreated()->assertJsonCount(2, 'data')->assertJsonPath('data.0.asset_code', 'UNIT-0001')->assertJsonPath('data.1.asset_code', 'UNIT-0002');
    $assetId = $response->json('data.0.id');
    test()->getJson('/api/v1/inventory/resolve?code=UNIT-0001')->assertJsonPath('data.asset.id', $assetId);
    test()->patchJson('/api/v1/inventory/assets/'.$assetId, ['location_id' => $data['location']->id, 'condition' => 'damaged', 'reason' => 'Kompas rusak ketika diperiksa'])->assertOk()->assertJsonPath('data.condition', 'damaged');
    test()->getJson('/api/v1/inventory/assets/'.$assetId.'/events')->assertJsonCount(1, 'data');
    test()->getJson('/api/v1/inventory/resolve?code=UNIT-0001')->assertUnprocessable();
});

it('runs the full loan lifecycle with automatic sources audit photos and verified BA', function () {
    Storage::fake('local');
    $data = inventoryApiFixture();
    $response = test()->postJson('/api/v1/inventory/loans', inventoryApiLoanPayload($data['item']->id));
    $response->assertCreated()->assertJsonPath('data.items.0.owner_id', $data['owner']->id)->assertJsonPath('data.items.0.location_id', $data['location']->id);
    $loanId = $response->json('data.id');
    $lineId = $response->json('data.items.0.id');
    test()->postJson('/api/v1/inventory/loans/'.$loanId.'/approve')->assertOk()->assertJsonPath('data.status', 'approved');
    test()->postJson('/api/v1/inventory/loans/'.$loanId.'/handover', ['recording_method' => 'online', 'lines' => [['loan_item_id' => $lineId, 'quantity' => 3]]])->assertOk()->assertJsonPath('data.status', 'handed_over');
    $events = test()->getJson('/api/v1/inventory/loans/'.$loanId.'/events')->assertOk();
    $eventId = $events->json('data.0.id');
    test()->post('/api/v1/inventory/events/'.$eventId.'/evidence', ['photo' => UploadedFile::fake()->image('test.jpg')], ['Accept' => 'application/json'])->assertCreated()->assertJsonMissingPath('data.path');
    $document = test()->postJson('/api/v1/inventory/loans/'.$loanId.'/documents/handover')->assertCreated()->assertJsonMissingPath('data.storage_path');
    $documentId = $document->json('data.id');
    test()->getJson('/api/v1/inventory/loans/'.$loanId.'/documents')->assertJsonCount(1, 'data');
    test()->get('/api/v1/inventory/documents/'.$documentId)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    test()->postJson('/api/v1/inventory/documents/'.$documentId.'/reprint', ['reason' => 'Salinan arsip'])->assertOk();
    test()->postJson('/api/v1/inventory/documents/'.$documentId.'/correct', ['reason' => 'Koreksi administrasi berita acara'])->assertCreated()->assertJsonPath('data.version', 2);
    test()->postJson('/api/v1/inventory/loans/'.$loanId.'/return', ['recording_method' => 'online', 'lines' => [['loan_item_id' => $lineId, 'quantity_returned' => 3, 'condition' => 'good']]])->assertOk()->assertJsonPath('data.status', 'returned');
    test()->postJson('/api/v1/inventory/loans/'.$loanId.'/documents/return')->assertCreated()->assertJsonPath('data.document_type', 'return');
    test()->assertDatabaseHas('inventory_stock_balances', ['item_id' => $data['item']->id, 'quantity_on_hand' => 20]);
});

it('rejects unknown nested loan fields and reports shortages without saving partial requests', function () {
    $data = inventoryApiFixture();
    $payload = inventoryApiLoanPayload($data['item']->id);
    $payload['items'][0]['owner_id'] = $data['owner']->id;
    test()->postJson('/api/v1/inventory/loans', $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0');
    test()->postJson('/api/v1/inventory/loans', inventoryApiLoanPayload($data['item']->id, 99))->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
    test()->assertDatabaseCount('inventory_loans', 0);
});

it('cancels pending loans and rejects invalid transaction state and offline timestamps', function () {
    $data = inventoryApiFixture();
    $response = test()->postJson('/api/v1/inventory/loans', inventoryApiLoanPayload($data['item']->id))->assertCreated();
    $id = $response->json('data.id');
    $lineId = $response->json('data.items.0.id');
    test()->postJson('/api/v1/inventory/loans/'.$id.'/return', ['recording_method' => 'offline', 'lines' => [['loan_item_id' => $lineId, 'quantity_returned' => 1]]])->assertUnprocessable()->assertJsonValidationErrors('occurred_at');
    test()->postJson('/api/v1/inventory/loans/'.$id.'/handover', ['recording_method' => 'online', 'lines' => [['loan_item_id' => $lineId, 'quantity' => 1]]])->assertUnprocessable();
    test()->postJson('/api/v1/inventory/loans/'.$id.'/cancel', ['reason' => 'Kegiatan tidak jadi dilaksanakan'])->assertOk()->assertJsonPath('data.status', 'cancelled');
    test()->postJson('/api/v1/inventory/loans/'.$id.'/approve')->assertUnprocessable();
});

it('does not grant a view-only inventory member mutation permissions', function (string $method, string $path) {
    $data = inventoryApiFixture();
    $member = User::factory()->create(['system_role' => null]);
    SchoolUserMembership::query()->create(['school_id' => $data['school']->id, 'user_id' => $member->id, 'is_active' => true]);
    setPermissionsTeamId($data['school']->id);
    Permission::findOrCreate('inventory.view', 'web');
    $member->givePermissionTo('inventory.view');
    Sanctum::actingAs($member);
    test()->json($method, '/api/v1/inventory/'.$path)->assertForbidden();
})->with([['POST', 'categories'], ['POST', 'assets'], ['POST', 'stock-movements'], ['POST', 'loans'], ['POST', 'loans/1/approve'], ['POST', 'loans/1/handover'], ['POST', 'loans/1/return'], ['GET', 'stock-movements']]);

it('uses the same real Bearer token for mobile activities and inventory without a session', function () {
    $data = inventoryApiFixture();
    auth()->forgetGuards();
    $response = test()->postJson('/api/v1/auth/login', [
        'email' => $data['user']->email, 'password' => 'password', 'device_name' => 'Android Retrofit',
    ])->assertOk()->assertJsonPath('data.token_type', 'Bearer');
    $token = $response->json('data.token');
    auth()->forgetGuards();
    test()->withToken($token)->getJson('/api/v1/activities')->assertOk();
    auth()->forgetGuards();
    test()->getJson('/api/v1/inventory/items')->assertOk()->assertJsonPath('data.0.id', $data['item']->id);
    auth()->forgetGuards();
    test()->postJson('/api/v1/inventory/loans', inventoryApiLoanPayload($data['item']->id))->assertCreated()->assertJsonPath('data.requested_by', $data['user']->id);
    auth()->forgetGuards();
    test()->postJson('/api/v1/auth/logout')->assertOk();
    auth()->forgetGuards();
    test()->getJson('/api/v1/inventory/items')->assertUnauthorized();
});

it('keeps mobile login blocked for accounts with two factor authentication', function () {
    test()->skipUnlessFortifyHas(Features::twoFactorAuthentication());
    $user = User::factory()->withTwoFactor()->create();

    test()->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password', 'device_name' => 'Android'])
        ->assertForbidden();
    test()->getJson('/api/v1/inventory/items')->assertUnauthorized();
});

it('allows Bearer inventory writes without CSRF while preserving web CSRF protection', function () {
    inventoryApiFixture();
    app()->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    });

    test()->postJson('/api/v1/inventory/categories', ['name' => 'Bearer Valid'])->assertCreated();
    test()->postJson('/login', ['email' => 'any@example.test', 'password' => 'password'])->assertStatus(419);
});

it('rejects expired Bearer tokens and inactive school memberships', function () {
    $data = inventoryApiFixture();
    $expired = $data['user']->createToken('expired', ['*'], now()->subMinute())->plainTextToken;
    auth()->forgetGuards();
    test()->withToken($expired)->getJson('/api/v1/inventory/items')->assertUnauthorized();
    $member = User::factory()->create(['system_role' => null]);
    SchoolUserMembership::query()->create(['school_id' => $data['school']->id, 'user_id' => $member->id, 'is_active' => false]);
    $token = $member->createToken('Android')->plainTextToken;
    auth()->forgetGuards();
    test()->withToken($token)->getJson('/api/v1/inventory/items')->assertForbidden();
});

it('clears school and permission context even when the next school header is rejected', function () {
    $data = inventoryApiFixture();
    test()->getJson('/api/v1/inventory/context')->assertOk();
    app(SchoolContext::class)->set($data['school']);
    setPermissionsTeamId($data['school']->id);

    test()->withHeader('X-School-Id', '99999999')->getJson('/api/v1/inventory/context')->assertNotFound();

    expect(app(SchoolContext::class)->id())->toBeNull();
    expect(getPermissionsTeamId())->toBeNull();
});

it('revokes a disabled users real mobile token before inventory access', function () {
    $data = inventoryApiFixture();
    $token = $data['user']->createToken('disabled Android')->plainTextToken;
    $data['user']->update(['is_active' => false]);
    auth()->forgetGuards();

    test()->withToken($token)->withHeader('X-School-Id', '')->getJson('/api/v1/inventory/items')->assertForbidden();

    expect($data['user']->tokens()->count())->toBe(0);
});
