<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLoanItem extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'loan_id', 'item_id', 'asset_id', 'owner_id', 'location_id',
        'quantity_requested', 'quantity_handed_over', 'quantity_returned',
        'quantity_missing', 'condition_out', 'condition_in', 'condition_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:3',
            'quantity_handed_over' => 'decimal:3',
            'quantity_returned' => 'decimal:3',
            'quantity_missing' => 'decimal:3',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(InventoryLoan::class, 'loan_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(InventoryAsset::class, 'asset_id');
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
