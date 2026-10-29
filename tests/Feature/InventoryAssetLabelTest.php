<?php

namespace Tests\Feature;

use App\Models\InventoryAsset;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAssetLabelTest extends TestCase
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

    public function test_asset_label_contains_qr_for_all_print_formats(): void
    {
        $asset = $this->makeAsset();

        foreach (['a4', 'thermal58', 'thermal80'] as $format) {
            $this->get(route('inventory.assets.labels', [
                'asset' => $asset->id, 'format' => $format,
            ]))->assertOk()->assertSee('ASSET-001')
                ->assertSee('data:image/png;base64,', false);
        }
    }

    public function test_asset_qr_verification_shows_individual_identity(): void
    {
        $asset = $this->makeAsset();
        $schoolId = app(SchoolContext::class)->id();

        $this->get(route('inventory.assets.verify', [
            'school' => $schoolId, 'asset' => $asset->id,
        ]))->assertOk()->assertSee('ASSET-001')->assertSee('Kompas');
    }

    public function test_other_school_cannot_access_asset_label_or_verification(): void
    {
        $asset = $this->makeAsset();
        $other = School::factory()->create();
        app(SchoolContext::class)->set($other);
        $this->withSession(['active_school_id' => $other->id]);

        $this->get(route('inventory.assets.labels', ['asset' => $asset->id]))->assertNotFound();
        $this->get(route('inventory.assets.verify', [
            'school' => $other->id, 'asset' => $asset->id,
        ]))->assertNotFound();
    }

    public function test_member_without_permissions_cannot_access_asset_qr(): void
    {
        $asset = $this->makeAsset();
        $schoolId = app(SchoolContext::class)->id();
        $member = User::factory()->create(['system_role' => null]);
        SchoolUserMembership::query()->create([
            'school_id' => $schoolId,
            'user_id' => $member->id,
            'is_active' => true,
        ]);
        $this->actingAs($member)->withSession(['active_school_id' => $schoolId]);

        $this->get(route('inventory.assets.labels', ['asset' => $asset->id]))->assertForbidden();
        $this->get(route('inventory.assets.verify', [
            'school' => $schoolId, 'asset' => $asset->id,
        ]))->assertForbidden();
    }

    public function test_bulk_asset_labels_render_all_formats_and_distinct_qr_codes(): void
    {
        $first = $this->makeAsset();
        $second = InventoryAsset::query()->create([
            'item_id' => $first->item_id,
            'owner_id' => $first->owner_id,
            'location_id' => $first->location_id,
            'asset_code' => 'ASSET-002',
            'condition' => 'good',
            'status' => 'available',
        ]);

        foreach (['a4', 'thermal58', 'thermal80'] as $format) {
            $this->get(route('inventory.assets.batch-labels', [
                'ids' => $first->id.','.$second->id,
                'format' => $format,
            ]))->assertOk()
                ->assertSee('ASSET-001')
                ->assertSee('ASSET-002')
                ->assertSee('data:image/png;base64,', false)
                ->assertViewHas('labels', function ($labels) use ($first, $second): bool {
                    return $labels->count() === 2
                        && $labels[0]['asset']->id === $first->id
                        && $labels[1]['asset']->id === $second->id
                        && $labels[0]['qrDataUri'] !== $labels[1]['qrDataUri'];
                });
        }
    }

    public function test_bulk_asset_labels_reject_duplicate_or_invalid_ids(): void
    {
        $asset = $this->makeAsset();

        $this->get(route('inventory.assets.batch-labels', [
            'ids' => $asset->id.','.$asset->id,
        ]))->assertSessionHasErrors('ids');

        $this->get(route('inventory.assets.batch-labels', [
            'ids' => 'abc',
        ]))->assertSessionHasErrors('ids');
    }

    public function test_bulk_asset_labels_reject_cross_school_assets(): void
    {
        $asset = $this->makeAsset();
        $other = School::factory()->create();
        app(SchoolContext::class)->set($other);
        $this->withSession(['active_school_id' => $other->id]);

        $this->get(route('inventory.assets.batch-labels', [
            'ids' => (string) $asset->id,
        ]))->assertNotFound();
    }

    public function test_bulk_asset_labels_require_print_permission(): void
    {
        $asset = $this->makeAsset();
        $schoolId = app(SchoolContext::class)->id();
        $member = User::factory()->create(['system_role' => null]);
        SchoolUserMembership::query()->create([
            'school_id' => $schoolId,
            'user_id' => $member->id,
            'is_active' => true,
        ]);
        $this->actingAs($member)->withSession(['active_school_id' => $schoolId]);

        $this->get(route('inventory.assets.batch-labels', [
            'ids' => (string) $asset->id,
        ]))->assertForbidden();
    }

    private function makeAsset(): InventoryAsset
    {
        $category = InventoryCategory::query()->create(['name' => 'Alat', 'is_active' => true]);
        $item = InventoryItem::query()->create([
            'category_id' => $category->id,
            'item_code' => 'ITEM-001',
            'name' => 'Kompas',
            'tracking_type' => 'individual',
            'unit' => 'unit',
            'minimum_stock' => 0,
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

        return InventoryAsset::query()->create([
            'item_id' => $item->id,
            'owner_id' => $owner->id,
            'location_id' => $location->id,
            'asset_code' => 'ASSET-001',
            'condition' => 'good',
            'status' => 'available',
        ]);
    }
}
