<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryIndexRequest;
use App\Http\Requests\InventoryLoanRequest;
use App\Http\Requests\InventoryTransactionRequest;
use App\Http\Resources\InventoryAssetResource;
use App\Http\Resources\InventoryDocumentResource;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\InventoryLoanResource;
use App\Http\Resources\InventoryRecordResource;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Services\Inventory\InventoryLoanSelectionService;
use App\Services\Inventory\InventoryLoanService;
use App\Support\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryLoanController extends Controller
{
    public function index(InventoryIndexRequest $request): AnonymousResourceCollection
    {
        $query = InventoryLoan::query()->where('school_id', app(SchoolContext::class)->id())->with(['items.item', 'documents.verification']);
        if ($request->filled('status')) {
            $query->where('status', $request->validated('status'));
        }
        if ($search = $request->validated('search')) {
            $query->where(function ($query) use ($search): void {
                $query->where('loan_number', 'like', '%'.$search.'%')->orWhere('borrower_name', 'like', '%'.$search.'%')
                    ->orWhere('borrower_organization', 'like', '%'.$search.'%');
            });
        }

        return InventoryLoanResource::collection($query->latest('requested_at')->orderByDesc('id')->paginate($request->integer('per_page', 20))->withQueryString());
    }

    public function show(InventoryIndexRequest $request, int $id): InventoryLoanResource
    {
        return $this->resource($id);
    }

    public function events(InventoryIndexRequest $request, int $id): AnonymousResourceCollection
    {
        $loan = InventoryLoan::query()->where('school_id', app(SchoolContext::class)->id())->findOrFail($id);

        return InventoryRecordResource::collection($loan->events()->with(['recorder', 'evidence'])->orderByDesc('occurred_at')->orderByDesc('id')->paginate($request->integer('per_page', 20))->withQueryString());
    }

    public function documents(InventoryIndexRequest $request, int $id): AnonymousResourceCollection
    {
        $loan = InventoryLoan::query()->where('school_id', app(SchoolContext::class)->id())->findOrFail($id);

        return InventoryDocumentResource::collection($loan->documents()->with('verification')->latest('issued_at')->orderByDesc('id')->paginate($request->integer('per_page', 20)));
    }

    public function resolve(InventoryIndexRequest $request, InventoryLoanSelectionService $service): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:2048']]);
        try {
            $selection = $service->resolve($data['code']);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['code' => $exception->errors()['itemCode'] ?? ['Kode tidak ditemukan.']]);
        }
        $item = $selection['item']->load(['category', 'stockBalances.owner', 'stockBalances.location'])->loadCount('assets')->loadSum('stockBalances as total_quantity', 'quantity_on_hand');

        return response()->json(['data' => [
            'item' => new InventoryItemResource($item),
            'asset' => $selection['asset'] ? new InventoryAssetResource($selection['asset']->load(['item', 'owner', 'location'])) : null,
        ]]);
    }

    public function store(InventoryLoanRequest $request, InventoryLoanService $service, InventoryLoanSelectionService $selection): JsonResponse
    {
        $data = $request->validated();
        $loan = DB::transaction(function () use ($data, $service, $selection): InventoryLoan {
            $cart = [];
            foreach ($data['items'] as $index => $line) {
                $item = InventoryItem::query()->where('school_id', app(SchoolContext::class)->id())->where('is_active', true)->whereKey($line['item_id'])->firstOrFail();
                try {
                    $cart = $selection->add($cart, $item, $line['quantity'], $line['asset_id'] ?? null);
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(['items.'.$index.'.quantity' => collect($exception->errors())->flatten()->all()]);
                }
            }

            return $service->requestLoan($data['borrower'], $cart, $data['purpose'], $data['notes'] ?? null);
        });

        return $this->resource($loan->id)->response()->setStatusCode(201);
    }

    public function approve(Request $request, int $id, InventoryLoanService $service): InventoryLoanResource
    {
        $service->approve($id);

        return $this->resource($id);
    }

    public function cancel(Request $request, int $id, InventoryLoanService $service): InventoryLoanResource
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $service->cancelPending($id, $data['reason']);

        return $this->resource($id);
    }

    public function transact(InventoryTransactionRequest $request, int $id, InventoryLoanService $service): InventoryLoanResource
    {
        $data = $request->validated();
        $at = $data['recording_method'] === 'offline' ? Carbon::parse($data['occurred_at']) : now();
        if ($request->routeIs('*.handover')) {
            $service->recordHandover($id, $data['lines'], $at, $data['recording_method'], $data['notes'] ?? null);
        } else {
            $service->recordReturn($id, $data['lines'], $at, $data['recording_method'], $data['notes'] ?? null);
        }

        return $this->resource($id);
    }

    private function resource(int $id): InventoryLoanResource
    {
        return new InventoryLoanResource(InventoryLoan::query()->where('school_id', app(SchoolContext::class)->id())
            ->with(['items.item', 'items.owner', 'items.location', 'documents.verification'])->findOrFail($id));
    }
}
