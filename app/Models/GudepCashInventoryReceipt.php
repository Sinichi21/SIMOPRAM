<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudepCashInventoryReceipt extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'gudep_cash_entry_id',
        'inventory_stock_movement_id', 'recorded_by', 'first_batch_token',
    ];

    /** @return BelongsTo<GudepCashEntry, $this> */
    public function cashEntry(): BelongsTo
    {
        return $this->belongsTo(GudepCashEntry::class, 'gudep_cash_entry_id');
    }

    /** @return BelongsTo<InventoryStockMovement, $this> */
    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryStockMovement::class, 'inventory_stock_movement_id');
    }

    /** @return HasMany<GudepCashInventoryReceiptItem, $this> */
    public function additionalItems(): HasMany
    {
        return $this->hasMany(GudepCashInventoryReceiptItem::class, 'gudep_cash_inventory_receipt_id');
    }

    /** @return HasMany<GudepCashInventoryCorrection, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(GudepCashInventoryCorrection::class, 'gudep_cash_inventory_receipt_id');
    }
}
