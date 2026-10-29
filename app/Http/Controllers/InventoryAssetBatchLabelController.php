<?php

namespace App\Http\Controllers;

use App\Models\InventoryAsset;
use App\Support\SchoolContext;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryAssetBatchLabelController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->can('inventory.labels.print'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        $data = $request->validate([
            'format' => ['sometimes', 'in:a4,thermal58,thermal80'],
            'ids' => ['required', 'string', 'max:6000', 'regex:/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/'],
        ]);
        $ids = array_map('intval', explode(',', $data['ids']));
        if (count($ids) > 500 || count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['ids' => 'Pilih maksimal 500 aset tanpa duplikasi.']);
        }

        $assets = InventoryAsset::query()->where('school_id', $schoolId)
            ->whereIn('id', $ids)->with(['item', 'owner'])->get()->keyBy('id');
        if ($assets->count() !== count($ids)) {
            abort(404);
        }

        $writer = new PngWriter;
        $labels = collect($ids)->map(function (int $id) use ($assets, $schoolId, $writer): array {
            $asset = $assets->get($id);
            $url = route('inventory.assets.verify', ['school' => $schoolId, 'asset' => $asset->id]);

            return [
                'asset' => $asset,
                'qrDataUri' => $writer->write(new QrCode(data: $url, size: 160, margin: 5))->getDataUri(),
            ];
        });

        return view('inventory.labels.asset-batch', [
            'labels' => $labels,
            'format' => $data['format'] ?? 'a4',
        ]);
    }
}
