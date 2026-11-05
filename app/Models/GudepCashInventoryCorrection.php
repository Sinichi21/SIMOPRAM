<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudepCashInventoryCorrection extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'gudep_cash_inventory_receipt_id',
        'original_stock_movement_id', 'correction_stock_movement_id',
        'quantity', 'reason', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    /** @return BelongsTo<GudepCashInventoryReceipt, $this> */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GudepCashInventoryReceipt::class, 'gudep_cash_inventory_receipt_id');
    }

    /** @return BelongsTo<InventoryStockMovement, $this> */
    public function originalMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryStockMovement::class, 'original_stock_movement_id');
    }

    /** @return BelongsTo<InventoryStockMovement, $this> */
    public function correctionMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryStockMovement::class, 'correction_stock_movement_id');
    }
}
