<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemLabelTest extends TestCase
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

    public function test_label_renders_all_supported_formats(): void
    {
        $item = $this->makeItem();

        foreach (['a4', 'thermal58', 'thermal80'] as $format) {
            $this->get(route('inventory.items.labels', ['item' => $item->id, 'format' => $format, 'copies' => 2]))
                ->assertOk()
                ->assertSee('Tali Pramuka')
                ->assertSee('LABEL-001')
                ->assertSee('Cetak label')
                ->assertSee('data:image/png;base64,', false);
        }
    }

    public function test_label_rejects_invalid_format_and_copy_count(): void
    {
        $item = $this->makeItem();

        $this->get(route('inventory.items.labels', ['item' => $item->id, 'format' => 'unknown']))
            ->assertSessionHasErrors('format');
        $this->get(route('inventory.items.labels', ['item' => $item->id, 'copies' => 51]))
            ->assertSessionHasErrors('copies');
    }

    public function test_label_cannot_access_item_from_another_school(): void
    {
        $item = $this->makeItem();
        $other = School::factory()->create();
        app(SchoolContext::class)->set($other);
        $this->withSession(['active_school_id' => $other->id]);

        $this->get(route('inventory.items.labels', ['item' => $item->id]))->assertNotFound();
    }

    public function test_member_without_label_permission_is_forbidden(): void
    {
        $item = $this->makeItem();
        $schoolId = app(SchoolContext::class)->id();
        $member = User::factory()->create(['system_role' => null]);
        SchoolUserMembership::query()->create([
            'school_id' => $schoolId,
            'user_id' => $member->id,
            'is_active' => true,
        ]);
        $this->actingAs($member)->withSession(['active_school_id' => $schoolId]);

        $this->get(route('inventory.items.labels', ['item' => $item->id]))->assertForbidden();
    }

    private function makeItem(): InventoryItem
    {
        $category = InventoryCategory::query()->create([
            'name' => 'Peralatan',
            'is_active' => true,
        ]);

        return InventoryItem::query()->create([
            'category_id' => $category->id,
            'item_code' => 'LABEL-001',
            'name' => 'Tali Pramuka',
            'tracking_type' => 'quantity',
            'unit' => 'unit',
            'minimum_stock' => 0,
            'is_active' => true,
        ]);
    }
}
