<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudepCashInventoryReceiptItem extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'gudep_cash_inventory_receipt_id',
        'inventory_stock_movement_id', 'receipt_batch_token', 'batch_line_no',
    ];

    /** @return BelongsTo<GudepCashInventoryReceipt, $this> */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GudepCashInventoryReceipt::class, 'gudep_cash_inventory_receipt_id');
    }

    /** @return BelongsTo<InventoryStockMovement, $this> */
    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryStockMovement::class, 'inventory_stock_movement_id');
    }
}
