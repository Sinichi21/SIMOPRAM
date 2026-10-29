<?php

namespace Tests\Feature;

use App\Livewire\Inventory\Index;
use App\Models\InventoryAsset;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryAssetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::factory()->create();
        app(SchoolContext::class)->set($school);
        $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
            ->withSession(['active_school_id' => $school->id]);
    }

    public function test_can_register_individual_asset(): void
    {
        [$item, $owner, $location] = $this->makeReferences();

        Livewire::test(Index::class)
            ->set('assetItemId', $item->id)
            ->set('assetOwnerId', $owner->id)
            ->set('assetLocationId', $location->id)
            ->set('assetCode', 'KOMPAS')
            ->set('assetSerialNumber', 'SERIAL-001')
            ->call('createAsset')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_assets', [
            'school_id' => app(SchoolContext::class)->id(),
            'item_id' => $item->id,
            'asset_code' => 'KOMPAS-0001',
            'serial_number' => 'SERIAL-001',
            'status' => 'available',
        ]);
    }

    public function test_generates_multiple_asset_codes_after_last_number_and_continues_sequence(): void
    {
        [$item, $owner, $location] = $this->makeReferences();

        Livewire::test(Index::class)
            ->set('assetItemId', $item->id)
            ->set('assetOwnerId', $owner->id)
            ->set('assetLocationId', $location->id)
            ->set('assetCode', 'TENDA')
            ->set('assetQuantity', '3')
            ->set('assetLastNumber', '12')
            ->call('createAsset')
            ->assertHasNoErrors();

        foreach ([13, 14, 15] as $number) {
            $this->assertDatabaseHas('inventory_assets', [
                'school_id' => app(SchoolContext::class)->id(),
                'asset_code' => 'TENDA-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            ]);
        }

        Livewire::test(Index::class)
            ->set('assetItemId', $item->id)
            ->set('assetOwnerId', $owner->id)
            ->set('assetLocationId', $location->id)
            ->set('assetCode', 'TENDA')
            ->set('assetQuantity', '2')
            ->set('assetLastNumber', '0')
            ->call('createAsset')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_assets', ['asset_code' => 'TENDA-0016']);
        $this->assertDatabaseHas('inventory_assets', ['asset_code' => 'TENDA-0017']);
        $this->assertDatabaseCount('inventory_assets', 5);
    }

    public function test_asset_code_preview_matches_batch_and_generated_label_ids(): void
    {
        [$item, $owner, $location] = $this->makeReferences();

        $component = Livewire::test(Index::class)
            ->set('assetItemId', $item->id)
            ->set('assetOwnerId', $owner->id)
            ->set('assetLocationId', $location->id)
            ->set('assetCode', 'KOMPAS')
            ->set('assetQuantity', '3')
            ->set('assetLastNumber', '7');

        $this->assertSame([
            'first' => 'KOMPAS-0008',
            'last' => 'KOMPAS-0010',
            'count' => 3,
        ], $component->instance()->assetCodePreview);

        $component->call('createAsset')->assertHasNoErrors();
        $ids = $component->get('lastCreatedAssetIds');
        $this->assertCount(3, $ids);
        $this->assertSame(
            ['KOMPAS-0008', 'KOMPAS-0009', 'KOMPAS-0010'],
            InventoryAsset::query()->whereIn('id', $ids)->orderBy('id')->pluck('asset_code')->all(),
        );
    }

    public function test_cannot_register_asset_against_other_school_owner(): void
    {
        [$item, , $location] = $this->makeReferences();
        $otherSchool = School::factory()->create();
        app(SchoolContext::class)->set($otherSchool);
        $foreignOwner = InventoryOwner::query()->create([
            'owner_type' => 'school', 'display_name' => 'Sekolah lain', 'is_active' => true,
        ]);
        $this->withSession(['active_school_id' => $otherSchool->id]);
        $ownerId = $foreignOwner->id;
        $school = School::query()->findOrFail($item->school_id);
        app(SchoolContext::class)->set($school);
        $this->withSession(['active_school_id' => $school->id]);

        Livewire::test(Index::class)
            ->set('assetItemId', $item->id)
            ->set('assetOwnerId', $ownerId)
            ->set('assetLocationId', $location->id)
            ->set('assetCode', 'KOMPAS')
            ->call('createAsset')
            ->assertHasErrors(['assetOwnerId']);

        $this->assertDatabaseCount('inventory_assets', 0);
    }

    public function test_asset_location_and_condition_change_is_audited(): void
    {
        [$item, $owner, $location] = $this->makeReferences();
        $next = InventoryLocation::query()->create([
            'name' => 'Ruang Peralatan', 'location_type' => 'room', 'is_active' => true,
        ]);
        $asset = InventoryAsset::query()->create([
            'item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id,
            'asset_code' => 'KOMPAS-003', 'condition' => 'good', 'status' => 'available',
        ]);

        Livewire::test(Index::class)
            ->call('editAsset', $asset->id)
            ->set('editingAssetLocationId', $next->id)
            ->set('editingAssetCondition', 'damaged')
            ->set('editingAssetReason', 'Kompas rusak setelah pemeriksaan.')
            ->call('saveAssetChange')
            ->assertHasNoErrors();

        $this->assertSame($next->id, $asset->fresh()->location_id);
        $this->assertSame('damaged', $asset->fresh()->condition);
        $this->assertDatabaseHas('inventory_asset_events', [
            'asset_id' => $asset->id,
            'from_location_id' => $location->id,
            'to_location_id' => $next->id,
            'from_condition' => 'good',
            'to_condition' => 'damaged',
        ]);
    }

    public function test_asset_change_rejects_short_reason_without_mutating_asset(): void
    {
        [$item, $owner, $location] = $this->makeReferences();
        $asset = InventoryAsset::query()->create([
            'item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id,
            'asset_code' => 'KOMPAS-004', 'condition' => 'good', 'status' => 'available',
        ]);

        Livewire::test(Index::class)
            ->call('editAsset', $asset->id)
            ->set('editingAssetCondition', 'damaged')
            ->set('editingAssetReason', 'Singkat')
            ->call('saveAssetChange')
            ->assertHasErrors(['editingAssetReason']);

        $this->assertSame('good', $asset->fresh()->condition);
        $this->assertDatabaseCount('inventory_asset_events', 0);
    }

    public function test_unavailable_asset_cannot_be_changed_manually_or_audited(): void
    {
        [$item, $owner, $location] = $this->makeReferences();
        $asset = InventoryAsset::query()->create([
            'item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id,
            'asset_code' => 'KOMPAS-005', 'condition' => 'good', 'status' => 'borrowed',
        ]);

        Livewire::test(Index::class)
            ->call('editAsset', $asset->id)
            ->set('editingAssetCondition', 'damaged')
            ->set('editingAssetReason', 'Kerusakan dilaporkan ketika aset masih dipinjam.')
            ->call('saveAssetChange')
            ->assertHasErrors(['editingAssetId']);

        $this->assertSame('good', $asset->fresh()->condition);
        $this->assertSame($location->id, $asset->fresh()->location_id);
        $this->assertDatabaseCount('inventory_asset_events', 0);
    }

    public function test_asset_change_rejects_location_from_another_school(): void
    {
        [$item, $owner, $location] = $this->makeReferences();
        $asset = InventoryAsset::query()->create([
            'item_id' => $item->id, 'owner_id' => $owner->id, 'location_id' => $location->id,
            'asset_code' => 'KOMPAS-006', 'condition' => 'good', 'status' => 'available',
        ]);
        $otherSchool = School::factory()->create();
        app(SchoolContext::class)->set($otherSchool);
        $foreignLocation = InventoryLocation::query()->create([
            'name' => 'Gudang sekolah lain', 'location_type' => 'storage', 'is_active' => true,
        ]);
        app(SchoolContext::class)->set(School::query()->findOrFail($item->school_id));
        $this->withSession(['active_school_id' => $item->school_id]);

        Livewire::test(Index::class)
            ->call('editAsset', $asset->id)
            ->set('editingAssetLocationId', $foreignLocation->id)
            ->set('editingAssetReason', 'Percobaan pemindahan ke lokasi sekolah lain.')
            ->call('saveAssetChange')
            ->assertHasErrors(['editingAssetLocationId']);

        $this->assertSame($location->id, $asset->fresh()->location_id);
        $this->assertDatabaseCount('inventory_asset_events', 0);
    }

    private function makeReferences(): array
    {
        $category = InventoryCategory::query()->create(['name' => 'Alat', 'is_active' => true]);
        $item = InventoryItem::query()->create([
            'category_id' => $category->id,
            'item_code' => 'KOMPAS',
            'name' => 'Kompas',
            'tracking_type' => 'individual',
            'unit' => 'unit',
            'minimum_stock' => 0,
            'is_active' => true,
        ]);
        $owner = InventoryOwner::query()->create([
            'owner_type' => 'school', 'display_name' => 'Sekolah', 'is_active' => true,
        ]);
        $location = InventoryLocation::query()->create([
            'name' => 'Gudang', 'location_type' => 'storage', 'is_active' => true,
        ]);

        return [$item, $owner, $location];
    }
}
