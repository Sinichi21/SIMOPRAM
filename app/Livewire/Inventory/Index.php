<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';
    public bool $showCategoryForm = false;
    public bool $showItemForm = false;

    public string $categoryName = '';
    public string $categoryDescription = '';
    public string $itemCode = '';
    public string $itemName = '';
    public string $itemDescription = '';
    public string $trackingType = 'quantity';
    public string $unit = 'unit';
    public string $minimumStock = '0';
    public ?int $categoryId = null;

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function createCategory(): void
    {
        $this->authorizeManage();

        $schoolId = $this->schoolId();
        $validated = $this->validate([
            'categoryName' => [
                'required', 'string', 'max:100',
                Rule::unique('inventory_categories', 'name')->where('school_id', $schoolId),
            ],
            'categoryDescription' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(fn () => InventoryCategory::query()->create([
            'school_id' => $schoolId,
            'name' => trim($validated['categoryName']),
            'description' => $validated['categoryDescription'] ?: null,
            'is_active' => true,
        ]));

        $this->reset('categoryName', 'categoryDescription', 'showCategoryForm');
        session()->flash('status', 'Kategori inventaris berhasil ditambahkan.');
    }

    public function createItem(): void
    {
        $this->authorizeManage();
        $schoolId = $this->schoolId();

        $validated = $this->validate([
            'categoryId' => [
                'required', 'integer',
                Rule::exists('inventory_categories', 'id')->where('school_id', $schoolId)->whereNull('deleted_at'),
            ],
            'itemCode' => [
                'required', 'string', 'max:60',
                Rule::unique('inventory_items', 'item_code')->where('school_id', $schoolId),
            ],
            'itemName' => ['required', 'string', 'max:180'],
            'itemDescription' => ['nullable', 'string', 'max:4000'],
            'trackingType' => ['required', Rule::in(['quantity', 'individual'])],
            'unit' => ['required', 'string', 'max:30'],
            'minimumStock' => ['required', 'numeric', 'min:0', 'max:99999999999'],
        ]);

        DB::transaction(fn () => InventoryItem::query()->create([
            'school_id' => $schoolId,
            'category_id' => $validated['categoryId'],
            'item_code' => strtoupper(trim($validated['itemCode'])),
            'name' => trim($validated['itemName']),
            'description' => $validated['itemDescription'] ?: null,
            'tracking_type' => $validated['trackingType'],
            'unit' => trim($validated['unit']),
            'minimum_stock' => $validated['minimumStock'],
            'is_active' => true,
        ]));

        $this->reset('categoryId', 'itemCode', 'itemName', 'itemDescription', 'trackingType', 'unit', 'minimumStock', 'showItemForm');
        $this->trackingType = 'quantity';
        $this->unit = 'unit';
        $this->minimumStock = '0';
        session()->flash('status', 'Barang inventaris berhasil ditambahkan.');
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
    }

    public function render(): View
    {
        $this->authorizeAccess();
        $schoolId = $this->schoolId();

        $categories = InventoryCategory::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $items = InventoryItem::query()
            ->where('school_id', $schoolId)
            ->with('category:id,name')
            ->withCount('assets')
            ->when($this->search !== '', fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where('name', 'like', $term)->orWhere('item_code', 'like', $term);
            }))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.inventory.index', compact('categories', 'items'));
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);
        abort_unless(app(SchoolContext::class)->hasSchool(), 409, 'Pilih sekolah aktif terlebih dahulu.');
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('inventory.manage'), 403);
        $this->schoolId();
    }

    private function schoolId(): int
    {
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $schoolId;
    }
}
