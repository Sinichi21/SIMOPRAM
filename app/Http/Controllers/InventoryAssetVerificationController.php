<?php

namespace App\Http\Controllers;

use App\Models\InventoryAsset;
use App\Support\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryAssetVerificationController extends Controller
{
    public function __invoke(Request $request, int $school, int $asset): View
    {
        abort_unless($request->user()?->can('inventory.view'), 403);

        $activeSchool = app(SchoolContext::class)->id();
        abort_unless($activeSchool, 409, 'Pilih sekolah aktif terlebih dahulu.');
        abort_unless((int) $activeSchool === $school, 404);

        $record = InventoryAsset::query()->where('school_id', $activeSchool)
            ->with(['item', 'owner', 'location'])->findOrFail($asset);

        return view('inventory.labels.verify-asset', ['asset' => $record]);
    }
}
