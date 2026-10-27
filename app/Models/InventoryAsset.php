<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryAsset extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'item_id', 'owner_id', 'location_id', 'asset_code', 'serial_number',
        'condition', 'status', 'purchased_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['purchased_at' => 'date'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(InventoryOwner::class, 'owner_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }
}
