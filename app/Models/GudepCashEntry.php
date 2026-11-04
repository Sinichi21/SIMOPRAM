<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudepCashEntry extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id',
        'activity_id', 'inventory_item_id', 'direction', 'category',
        'occurred_at', 'description', 'amount', 'reference_number',
        'notes', 'status', 'created_by', 'posted_by', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'occurred_at' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    /** @return HasMany<GudepCashEvidence, $this> */
    public function evidences(): HasMany
    {
        return $this->hasMany(GudepCashEvidence::class, 'gudep_cash_entry_id');
    }

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
