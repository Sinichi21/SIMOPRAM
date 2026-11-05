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
        'reversal_of_id', 'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'occurred_at' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<GudepCashEntry, $this> */
    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    /** @return HasMany<GudepCashEntry, $this> */
    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    /** @return HasMany<GudepCashEvidence, $this> */
    public function evidences(): HasMany
    {
        return $this->hasMany(GudepCashEvidence::class, 'gudep_cash_entry_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<GudepCashInventoryReceipt, $this> */
    public function inventoryReceipt(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(GudepCashInventoryReceipt::class, 'gudep_cash_entry_id');
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
