<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockMovement extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'item_id', 'owner_id', 'location_id', 'movement_type', 'quantity',
        'balance_after', 'reference', 'notes', 'actor_id', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'balance_after' => 'decimal:3',
            'occurred_at' => 'datetime',
        ];
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
