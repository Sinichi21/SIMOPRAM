<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\DocumentSignatoryService;
use App\Support\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class InventorySessionController extends Controller
{
    public function schools(Request $request): JsonResponse
    {
        $query = School::query()->where('is_active', true);
        if (! $request->user()->isSystemAdmin()) {
            $query->whereIn('id', $request->user()->schoolMemberships()->where('is_active', true)->whereNull('left_at')->select('school_id'));
        }

        return response()->json(['data' => $query->orderBy('name')->get(['id', 'name'])]);
    }

    public function context(Request $request): JsonResponse
    {
        $permissions = [
            'inventory.view', 'inventory.manage', 'inventory.stock.manage', 'inventory.loans.request',
            'inventory.loans.approve', 'inventory.loans.record_manual', 'inventory.handover.process',
            'inventory.returns.process', 'inventory.labels.print', 'inventory.documents.reprint',
            'inventory.documents.correct', 'inventory.audit.view',
        ];

        return response()->json(['data' => [
            'school_id' => app(SchoolContext::class)->id(), 'timezone' => config('app.timezone'),
            'officer' => app(DocumentSignatoryService::class)->identityFor($request->user(), app(SchoolContext::class)->id()),
            'permissions' => collect($permissions)->mapWithKeys(fn ($permission) => [$permission => $request->user()->can($permission)]),
            'authentication' => 'sanctum_bearer', 'school_header' => 'X-School-Id', 'max_per_page' => 100,
            'quantity_format' => 'JSON number without locale separators; fractional quantities retain source precision',
            'master_deactivation' => 'PATCH is_active=false; stock, transaction and issued-document history is never deleted',
            'catalogs' => ['categories', 'owners', 'locations', 'items'],
            'enums' => [
                'tracking_type' => ['quantity', 'individual'], 'owner_type' => ['school', 'gudep'],
                'location_type' => ['storage', 'room', 'vehicle', 'other'],
                'movement_type' => ['receipt', 'issue', 'adjustment'], 'condition' => ['good', 'damaged', 'lost', 'missing'],
                'loan_status' => ['requested', 'cancelled', 'approved', 'partially_handed_over', 'handed_over', 'partially_returned', 'returned', 'discrepancy'],
                'asset_status' => ['available', 'on_loan', 'damaged', 'missing'],
                'borrower_type' => ['manual_external', 'internal', 'registered_gudep'],
                'recording_method' => ['online', 'offline'], 'document_type' => ['handover', 'return'],
            ],
            'endpoints' => collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with((string) $route->getName(), 'api.v1.inventory.'))->map(fn ($route) => ['name' => $route->getName(), 'methods' => $route->methods(), 'uri' => $route->uri()])->values(),
            'examples' => [
                'category' => ['name' => 'Peralatan', 'description' => 'Kategori barang'],
                'owner' => ['owner_type' => 'school', 'display_name' => 'Sekolah'],
                'location' => ['name' => 'Gudang', 'location_type' => 'storage'],
                'item' => ['category_id' => 1, 'item_code' => 'TALI-001', 'name' => 'Tali', 'tracking_type' => 'quantity', 'unit' => 'meter', 'minimum_stock' => 0],
                'asset_batch' => ['item_id' => 1, 'owner_id' => 1, 'location_id' => 1, 'code_prefix' => 'KOMPAS', 'quantity' => 2, 'last_number' => 0],
                'asset_update' => ['location_id' => 1, 'condition' => 'damaged', 'reason' => 'Penjelasan kondisi minimal sepuluh karakter'],
                'stock_movement' => ['item_id' => 1, 'owner_id' => 1, 'location_id' => 1, 'movement_type' => 'receipt', 'quantity' => 10],
                'evidence' => ['content_type' => 'multipart/form-data', 'field' => 'photo', 'formats' => ['jpg', 'jpeg', 'png', 'webp'], 'max_kb' => 5120, 'max_files_per_event' => 5],
                'document_issue' => ['type' => 'handover'],
                'document_correction_or_loan_cancellation' => ['reason' => 'Alasan administratif minimal sepuluh karakter'],
                'loan_request' => ['borrower' => ['type' => 'manual_external', 'name' => 'Nama Peminjam', 'contact' => '08xxxxxxxx'], 'purpose' => 'Latihan', 'items' => [['item_id' => 1, 'quantity' => 2]]],
                'handover' => ['recording_method' => 'online', 'lines' => [['loan_item_id' => 1, 'quantity' => 2]]],
                'return' => ['recording_method' => 'online', 'lines' => [['loan_item_id' => 1, 'quantity_returned' => 2, 'quantity_missing' => 0, 'condition' => 'good']]],
            ],
        ]])->header('Cache-Control', 'private, no-store');
    }
}
