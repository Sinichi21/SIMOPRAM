<?php

namespace Tests\Feature;

use App\Livewire\Inventory\Index;
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
            ->set('assetCode', 'KOMPAS-001')
            ->set('assetSerialNumber', 'SERIAL-001')
            ->call('createAsset')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_assets', [
            'school_id' => app(SchoolContext::class)->id(),
            'item_id' => $item->id,
            'asset_code' => 'KOMPAS-001',
            'serial_number' => 'SERIAL-001',
            'status' => 'available',
        ]);
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
            ->set('assetCode', 'KOMPAS-002')
            ->call('createAsset')
            ->assertHasErrors(['assetOwnerId']);

        $this->assertDatabaseCount('inventory_assets', 0);
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
