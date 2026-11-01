<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'item_code', 'name', 'description',
        'tracking_type', 'unit', 'minimum_stock', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(InventoryAsset::class, 'item_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(InventoryStockBalance::class, 'item_id');
    }
}
