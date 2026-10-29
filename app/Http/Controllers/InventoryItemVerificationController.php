<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Support\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemVerificationController extends Controller
{
    public function __invoke(Request $request, int $school, int $item): View
    {
        abort_unless($request->user()?->can('inventory.view'), 403);

        $activeSchool = app(SchoolContext::class)->id();
        abort_unless($activeSchool, 409, 'Pilih sekolah aktif terlebih dahulu.');
        abort_unless($activeSchool === $school, 404);

        $record = InventoryItem::query()
            ->where('school_id', $activeSchool)
            ->with('category')
            ->findOrFail($item);

        return view('inventory.labels.verify', ['item' => $record]);
    }
}
