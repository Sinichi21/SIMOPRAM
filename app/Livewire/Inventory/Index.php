<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryAsset;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Models\InventoryStockMovement;
use App\Models\ScoutGroup;
use App\Services\Inventory\InventoryStockService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCategoryForm = false;

    public bool $showAssetForm = false;

    public ?int $assetItemId = null;

    public ?int $assetOwnerId = null;

    public ?int $assetLocationId = null;

    public string $assetCode = '';

    public string $assetSerialNumber = '';

    public string $assetNotes = '';

    public bool $showItemForm = false;

    public bool $showOwnerForm = false;

    public bool $showLocationForm = false;

    public bool $showMovementForm = false;

    public string $categoryName = '';

    public string $categoryDescription = '';

    public string $itemCode = '';

    public string $itemName = '';

    public string $itemDescription = '';

    public string $trackingType = 'quantity';

    public string $unit = 'unit';

    public string $minimumStock = '0';

    public ?int $categoryId = null;

    public string $ownerType = 'school';

    public ?int $ownerScoutGroupId = null;

    public string $ownerDisplayName = '';

    public string $locationName = '';

    public string $locationType = 'storage';

    public ?int $locationScoutGroupId = null;

    public string $locationDescription = '';

    public ?int $movementItemId = null;

    public ?int $movementOwnerId = null;

    public ?int $movementLocationId = null;

    public string $movementType = 'receipt';

    public string $movementQuantity = '1';

    public string $movementReference = '';

    public string $movementNotes = '';

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function createCategory(): void
    {
        $this->authorizeManage();
        $schoolId = $this->schoolId();
        $this->categoryName = trim($this->categoryName);
        $validated = $this->validate([
            'categoryName' => ['required', 'string', 'max:100', Rule::unique('inventory_categories', 'name')->where('school_id', $schoolId)],
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
        $this->itemCode = strtoupper(trim($this->itemCode));
        $this->itemName = trim($this->itemName);
        $this->unit = trim($this->unit);
        $validated = $this->validate([
            'categoryId' => ['required', 'integer', Rule::exists('inventory_categories', 'id')->where('school_id', $schoolId)->whereNull('deleted_at')],
            'itemCode' => ['required', 'string', 'max:60', Rule::unique('inventory_items', 'item_code')->where('school_id', $schoolId)->whereNull('deleted_at')],
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

    public function createAsset(): void
    {
        $this->authorizeManage();
        $schoolId = $this->schoolId();
        $this->assetCode = strtoupper(trim($this->assetCode));
        $this->assetSerialNumber = trim($this->assetSerialNumber);

        $validated = $this->validate([
            'assetItemId' => ['required', 'integer', Rule::exists('inventory_items', 'id')->where('school_id', $schoolId)->where('tracking_type', 'individual')->whereNull('deleted_at')],
            'assetOwnerId' => ['required', 'integer', Rule::exists('inventory_owners', 'id')->where('school_id', $schoolId)->whereNull('deleted_at')],
            'assetLocationId' => ['required', 'integer', Rule::exists('inventory_locations', 'id')->where('school_id', $schoolId)->whereNull('deleted_at')],
            'assetCode' => ['required', 'string', 'max:80', Rule::unique('inventory_assets', 'asset_code')->where('school_id', $schoolId)->whereNull('deleted_at')],
            'assetSerialNumber' => ['nullable', 'string', 'max:120'],
            'assetNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        InventoryAsset::query()->create([
            'school_id' => $schoolId,
            'item_id' => $validated['assetItemId'],
            'owner_id' => $validated['assetOwnerId'],
            'location_id' => $validated['assetLocationId'],
            'asset_code' => $validated['assetCode'],
            'serial_number' => $validated['assetSerialNumber'] ?: null,
            'condition' => 'good',
            'status' => 'available',
            'notes' => $validated['assetNotes'] ?: null,
        ]);

        $this->reset('assetItemId', 'assetOwnerId', 'assetLocationId', 'assetCode', 'assetSerialNumber', 'assetNotes', 'showAssetForm');
        session()->flash('status', 'Unit aset berhasil didaftarkan.');
    }

    public function createOwner(): void
    {
        $this->authorizeManage();
        $schoolId = $this->schoolId();
        $this->ownerDisplayName = trim($this->ownerDisplayName);
        $validated = $this->validate([
            'ownerType' => ['required', Rule::in(['school', 'gudep'])],
            'ownerScoutGroupId' => [
                Rule::requiredIf($this->ownerType === 'gudep'),
                'nullable', 'integer',
                Rule::exists('scout_groups', 'id')->where('school_id', $schoolId),
            ],
            'ownerDisplayName' => ['required', 'string', 'max:150'],
        ]);

        InventoryOwner::query()->create([
            'school_id' => $schoolId,
            'owner_type' => $validated['ownerType'],
            'scout_group_id' => $validated['ownerType'] === 'gudep' ? $validated['ownerScoutGroupId'] : null,
            'display_name' => trim($validated['ownerDisplayName']),
            'is_active' => true,
        ]);

        $this->reset('ownerType', 'ownerScoutGroupId', 'ownerDisplayName', 'showOwnerForm');
        $this->ownerType = 'school';
        session()->flash('status', 'Pemilik inventaris berhasil ditambahkan.');
    }

    public function createLocation(): void
    {
        $this->authorizeManage();
        $schoolId = $this->schoolId();
        $this->locationName = trim($this->locationName);
        $validated = $this->validate([
            'locationName' => ['required', 'string', 'max:120', Rule::unique('inventory_locations', 'name')->where('school_id', $schoolId)->whereNull('deleted_at')],
            'locationType' => ['required', Rule::in(['storage', 'room', 'vehicle', 'other'])],
            'locationScoutGroupId' => ['nullable', 'integer', Rule::exists('scout_groups', 'id')->where('school_id', $schoolId)->whereNull('deleted_at')],
            'locationDescription' => ['nullable', 'string', 'max:2000'],
        ]);

        InventoryLocation::query()->create([
            'school_id' => $schoolId,
            'scout_group_id' => $validated['locationScoutGroupId'] ?: null,
            'name' => trim($validated['locationName']),
            'location_type' => $validated['locationType'],
            'description' => $validated['locationDescription'] ?: null,
            'is_active' => true,
        ]);

        $this->reset('locationName', 'locationType', 'locationScoutGroupId', 'locationDescription', 'showLocationForm');
        $this->locationType = 'storage';
        session()->flash('status', 'Lokasi inventaris berhasil ditambahkan.');
    }

    public function recordMovement(InventoryStockService $service): void
    {
        abort_unless(auth()->user()?->can('inventory.stock.manage'), 403);
        $this->schoolId();
        $validated = $this->validate([
            'movementItemId' => ['required', 'integer'],
            'movementOwnerId' => ['required', 'integer'],
            'movementLocationId' => ['required', 'integer'],
            'movementType' => ['required', Rule::in(['receipt', 'issue', 'adjustment'])],
            'movementQuantity' => ['required', 'numeric', 'min:0.001', 'max:99999999999'],
            'movementReference' => ['nullable', 'string', 'max:100'],
            'movementNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->recordMovement(
            (int) $validated['movementItemId'],
            (int) $validated['movementOwnerId'],
            (int) $validated['movementLocationId'],
            $validated['movementType'],
            $validated['movementQuantity'],
            $validated['movementReference'] ?: null,
            $validated['movementNotes'] ?: null,
        );

        $this->reset('movementItemId', 'movementOwnerId', 'movementLocationId', 'movementQuantity', 'movementReference', 'movementNotes', 'showMovementForm');
        $this->movementType = 'receipt';
        $this->movementQuantity = '1';
        session()->flash('status', 'Pergerakan stok berhasil dicatat.');
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizeAccess();
        $schoolId = $this->schoolId();

        $categories = InventoryCategory::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get();
        $groups = ScoutGroup::query()->where('school_id', $schoolId)->orderBy('name')->get();
        $owners = InventoryOwner::query()->where('school_id', $schoolId)->where('is_active', true)->with('scoutGroup')->orderBy('display_name')->get();
        $locations = InventoryLocation::query()->where('school_id', $schoolId)->where('is_active', true)->with('scoutGroup')->orderBy('name')->get();
        $individualItems = InventoryItem::query()->where('school_id', $schoolId)->where('is_active', true)->where('tracking_type', 'individual')->orderBy('name')->get();
        $assets = InventoryAsset::query()->where('school_id', $schoolId)->with(['item:id,name,item_code', 'owner:id,display_name', 'location:id,name'])->latest('id')->limit(30)->get();
        $quantityItems = InventoryItem::query()->where('school_id', $schoolId)->where('is_active', true)->where('tracking_type', 'quantity')->orderBy('name')->get();

        $items = InventoryItem::query()
            ->where('school_id', $schoolId)
            ->with('category:id,name')
            ->withCount('assets')
            ->withSum('stockBalances as total_quantity', 'quantity_on_hand')
            ->when($this->search !== '', fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where('name', 'like', $term)->orWhere('item_code', 'like', $term);
            }))
            ->orderBy('name')
            ->paginate(15);

        $movements = InventoryStockMovement::query()
            ->where('school_id', $schoolId)
            ->with(['item:id,name,item_code', 'owner:id,display_name', 'location:id,name'])
            ->latest('occurred_at')
            ->limit(10)
            ->get();

        return view('livewire.inventory.index', compact('categories', 'groups', 'owners', 'locations', 'quantityItems', 'individualItems', 'assets', 'items', 'movements'));
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);
        $this->schoolId();
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
