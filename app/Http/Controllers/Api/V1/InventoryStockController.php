<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryIndexRequest;
use App\Http\Requests\InventoryMovementRequest;
use App\Http\Resources\InventoryRecordResource;
use App\Models\InventoryStockBalance;
use App\Models\InventoryStockMovement;
use App\Services\Inventory\InventoryStockService;
use App\Support\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class InventoryStockController extends Controller
{
    public function balances(InventoryIndexRequest $request): AnonymousResourceCollection
    {
        $query = InventoryStockBalance::query()->where('school_id', app(SchoolContext::class)->id())->with(['item', 'owner', 'location']);
        foreach (['item_id', 'owner_id', 'location_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->validated($field));
            }
        }

        return InventoryRecordResource::collection($query->orderBy('id')->paginate($request->integer('per_page', 20))->withQueryString());
    }

    public function movements(InventoryIndexRequest $request): AnonymousResourceCollection
    {
        $query = InventoryStockMovement::query()->where('school_id', app(SchoolContext::class)->id())->with(['item', 'owner', 'location', 'actor']);
        foreach (['item_id', 'owner_id', 'location_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->validated($field));
            }
        }

        return InventoryRecordResource::collection($query->latest('occurred_at')->orderByDesc('id')->paginate($request->integer('per_page', 20))->withQueryString());
    }

    public function store(InventoryMovementRequest $request, InventoryStockService $service): JsonResponse
    {
        $data = $request->validated();
        $movement = $service->recordMovement($data['item_id'], $data['owner_id'], $data['location_id'], $data['movement_type'], $data['quantity'], $data['reference'] ?? null, $data['notes'] ?? null, isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : null);

        return (new InventoryRecordResource($movement->load(['item', 'owner', 'location', 'actor'])))->response()->setStatusCode(201);
    }
}
