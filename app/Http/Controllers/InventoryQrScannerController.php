<?php

namespace App\Http\Controllers;

use App\Support\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryQrScannerController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->can('inventory.view'), 403);
        abort_unless(app(SchoolContext::class)->id(), 409, 'Pilih sekolah aktif terlebih dahulu.');

        return view('inventory.labels.scanner');
    }
}
