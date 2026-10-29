<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Support\SchoolContext;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemLabelController extends Controller
{
    public function __invoke(Request $request, int $item): View
    {
        abort_unless($request->user()?->can('inventory.labels.print'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        $data = $request->validate([
            'format' => ['sometimes', 'in:a4,thermal58,thermal80'],
            'copies' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $record = InventoryItem::query()->where('school_id', $schoolId)
            ->with('category')->findOrFail($item);

        $qrPayload = route('inventory.items.verify', ['school' => $schoolId, 'item' => $record->id]);
        $qr = (new PngWriter)->write(new QrCode(data: $qrPayload, size: 160, margin: 5));

        return view('inventory.labels.item', [
            'item' => $record,
            'format' => $data['format'] ?? 'a4',
            'copies' => (int) ($data['copies'] ?? 1),
            'qrDataUri' => $qr->getDataUri(),
        ]);
    }
}
