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
            'scope' => ['sometimes', 'in:ids,item,all'],
            'item_id' => ['required_if:scope,item', 'nullable', 'integer'],
            'ids' => ['required_unless:scope,item,all', 'nullable', 'string', 'max:6000', 'regex:/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/'],
        ]);

        $scope = $data['scope'] ?? 'ids';
        $query = InventoryAsset::query()->where('school_id', $schoolId)->with(['item', 'owner']);
        if ($scope === 'item') {
            $itemId = (int) $data['item_id'];
            abort_unless(\\App\\Models\\InventoryItem::query()->where('school_id', $schoolId)
                ->where('tracking_type', 'individual')->whereKey($itemId)->exists(), 404);
            $query->where('item_id', $itemId);
        }

        if ($scope === 'ids') {
            $ids = array_map('intval', explode(',', $data['ids']));
            if (count($ids) > 500 || count($ids) !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['ids' => 'Pilih maksimal 500 aset tanpa duplikasi.']);
            }
            $assets = $query->whereIn('id', $ids)->get()->keyBy('id');
            abort_unless($assets->count() === count($ids), 404);
            $assets = collect($ids)->map(fn (int $id) => $assets->get($id));
        } else {
            $assets = $query->orderBy('id')->limit(501)->get();
            if ($assets->count() > 500) {
                throw ValidationException::withMessages(['scope' => 'Maksimal 500 label per cetak. Gunakan pemilihan unit untuk mencetak sebagian.']);
            }
        }

        $writer = new PngWriter;
        $labels = $assets->map(function (InventoryAsset $asset) use ($schoolId, $writer): array {
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
