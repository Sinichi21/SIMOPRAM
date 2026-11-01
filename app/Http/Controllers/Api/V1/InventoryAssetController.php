<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryAssetRequest;
use App\Http\Requests\InventoryIndexRequest;
use App\Http\Resources\InventoryAssetResource;
use App\Http\Resources\InventoryRecordResource;
use App\Models\InventoryAsset;
use App\Services\Inventory\InventoryAssetRegistrationService;
use App\Services\Inventory\InventoryAssetService;
use App\Support\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryAssetController extends Controller
{
    public function index(InventoryIndexRequest $request): AnonymousResourceCollection
    {
        $query = InventoryAsset::query()->where('school_id', app(SchoolContext::class)->id())->with(['item', 'owner', 'location']);
        foreach (['item_id', 'owner_id', 'location_id', 'status', 'condition'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->validated($field));
            }
        }
        if ($search = $request->validated('search')) {
            $query->where(function ($query) use ($search): void {
                $query->where('asset_code', 'like', '%'.$search.'%')->orWhere('serial_number', 'like', '%'.$search.'%')
                    ->orWhereHas('item', fn ($item) => $item->where('name', 'like', '%'.$search.'%'));
            });
        }

        return InventoryAssetResource::collection($query->orderBy('asset_code')->paginate($request->integer('per_page', 20))->withQueryString());
    }

    public function show(InventoryIndexRequest $request, int $id): InventoryAssetResource
    {
        return new InventoryAssetResource(InventoryAsset::query()->where('school_id', app(SchoolContext::class)->id())
            ->with(['item', 'owner', 'location'])->findOrFail($id));
    }

    public function events(InventoryIndexRequest $request, int $id): AnonymousResourceCollection
    {
        $asset = InventoryAsset::query()->where('school_id', app(SchoolContext::class)->id())->findOrFail($id);

        return InventoryRecordResource::collection($asset->events()->with(['actor', 'fromLocation', 'toLocation'])->orderByDesc('occurred_at')->orderByDesc('id')->paginate($request->integer('per_page', 20)));
    }

    public function store(InventoryAssetRequest $request, InventoryAssetRegistrationService $service): JsonResponse
    {
        $data = $request->validated();
        $ids = $service->register([
            'assetItemId' => $data['item_id'], 'assetOwnerId' => $data['owner_id'], 'assetLocationId' => $data['location_id'],
            'assetCode' => $data['code_prefix'], 'assetQuantity' => $data['quantity'], 'assetLastNumber' => $data['last_number'] ?? 0,
            'assetSerialNumber' => $data['serial_number'] ?? '', 'assetNotes' => $data['notes'] ?? '',
        ]);

        return InventoryAssetResource::collection(InventoryAsset::query()->whereIn('id', $ids)->with(['item', 'owner', 'location'])->orderBy('id')->get())->response()->setStatusCode(201);
    }

    public function update(InventoryAssetRequest $request, int $id, InventoryAssetService $service): InventoryAssetResource
    {
        $data = $request->validated();
        $asset = $service->updateLocationAndCondition($id, $data['location_id'], $data['condition'], $data['reason']);

        return new InventoryAssetResource($asset->load(['item', 'owner', 'location']));
    }
}
