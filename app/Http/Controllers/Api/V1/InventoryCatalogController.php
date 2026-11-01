<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryCatalogRequest;
use App\Http\Requests\InventoryIndexRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\InventoryRecordResource;
use App\Models\InventoryAsset;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryOwner;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryCatalogController extends Controller
{
    /** @var array<string, class-string<InventoryCategory|InventoryItem|InventoryOwner|InventoryLocation>> */
    public const MODELS = ['categories' => InventoryCategory::class, 'owners' => InventoryOwner::class, 'locations' => InventoryLocation::class, 'items' => InventoryItem::class];

    public function index(InventoryIndexRequest $request, string $catalog): AnonymousResourceCollection
    {
        $query = $this->query($catalog);
        $name = $catalog === 'owners' ? 'display_name' : 'name';
        if ($search = $request->validated('search')) {
            $query->where(function (Builder $query) use ($search, $name, $catalog): void {
                $query->where($name, 'like', '%'.$search.'%');
                if ($catalog === 'items') {
                    $query->orWhere('item_code', 'like', '%'.$search.'%');
                }
            });
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($catalog === 'items') {
            $query->with('category')->withCount('assets')->withSum('stockBalances as total_quantity', 'quantity_on_hand');
            foreach (['tracking_type', 'category_id'] as $field) {
                if ($request->filled($field)) {
                    $query->where($field, $request->validated($field));
                }
            }
        }
        $records = $query->orderBy($name)->paginate($request->integer('per_page', 20))->withQueryString();

        return $catalog === 'items' ? InventoryItemResource::collection($records) : InventoryRecordResource::collection($records);
    }

    public function show(InventoryIndexRequest $request, string $catalog, int $id): InventoryRecordResource
    {
        $record = $this->query($catalog)->findOrFail($id);
        if ($catalog === 'items') {
            $record->load(['category', 'stockBalances.owner', 'stockBalances.location'])->loadCount('assets')->loadSum('stockBalances as total_quantity', 'quantity_on_hand');

            return new InventoryItemResource($record);
        }

        return new InventoryRecordResource($record);
    }

    public function store(InventoryCatalogRequest $request, string $catalog): JsonResponse
    {
        $record = $this->save($request, $catalog);

        return ($catalog === 'items' ? new InventoryItemResource($record) : new InventoryRecordResource($record))->response()->setStatusCode(201);
    }

    public function update(InventoryCatalogRequest $request, string $catalog, int $id): InventoryRecordResource
    {
        $record = $this->save($request, $catalog, $id);

        return $catalog === 'items' ? new InventoryItemResource($record) : new InventoryRecordResource($record);
    }

    /** @return Builder<InventoryCategory>|Builder<InventoryOwner>|Builder<InventoryLocation>|Builder<InventoryItem> */
    private function query(string $catalog): Builder
    {
        abort_unless(isset(self::MODELS[$catalog]), 404);

        return self::MODELS[$catalog]::query()->where('school_id', app(SchoolContext::class)->id());
    }

    private function save(InventoryCatalogRequest $request, string $catalog, ?int $id = null): InventoryCategory|InventoryItem|InventoryOwner|InventoryLocation
    {
        return DB::transaction(function () use ($request, $catalog, $id) {
            $model = self::MODELS[$catalog];
            $record = $id ? $this->query($catalog)->lockForUpdate()->findOrFail($id) : new $model;
            $data = $request->validated();
            if ($record instanceof InventoryOwner) {
                $type = $data['owner_type'] ?? $record->owner_type;
                $group = $data['scout_group_id'] ?? $record->scout_group_id;
                if ($type === 'gudep' && ! $group) {
                    throw ValidationException::withMessages(['scout_group_id' => 'Pemilik Gudep harus memiliki referensi Gudep.']);
                }
                $data['scout_group_id'] = $type === 'school' ? null : $group;
            }
            $record->fill($data);
            if ($id && ($record instanceof InventoryItem || $record instanceof InventoryOwner || $record instanceof InventoryLocation)) {
                $structural = match ($catalog) {
                    'items' => ['tracking_type', 'unit', 'item_code'], 'owners' => ['owner_type', 'scout_group_id'], default => ['scout_group_id']
                };
                if ($record->isDirty($structural) && (InventoryAsset::withTrashed()->where(match ($catalog) {
                    'items' => 'item_id', 'owners' => 'owner_id', default => 'location_id'
                }, $record->getKey())->exists() || $record->stockBalances()->exists())) {
                    throw ValidationException::withMessages(['catalog' => 'Identitas dan jenis data yang sudah memiliki stok/aset tidak dapat diubah. Nonaktifkan data atau buat data baru.']);
                }
            }
            if ($record->getAttribute('is_active') === null) {
                $record->setAttribute('is_active', true);
            }
            $record->save();
            if ($record instanceof InventoryItem) {
                $record->load('category')->loadCount('assets')->loadSum('stockBalances as total_quantity', 'quantity_on_hand');
            }

            return $record;
        });
    }
}
