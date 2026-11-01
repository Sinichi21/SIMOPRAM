<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryLocation extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = ['scout_group_id', 'name', 'location_type', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scoutGroup(): BelongsTo
    {
        return $this->belongsTo(ScoutGroup::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(InventoryAsset::class, 'location_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(InventoryStockBalance::class, 'location_id');
    }
}
