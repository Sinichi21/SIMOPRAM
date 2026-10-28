<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLoanEvent extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'loan_id', 'loan_item_id', 'event_type', 'quantity', 'condition', 'notes',
        'occurred_at', 'recorded_at', 'recorded_by', 'recording_method',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'occurred_at' => 'datetime',
            'recorded_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(InventoryLoan::class, 'loan_id');
    }

    public function loanItem(): BelongsTo
    {
        return $this->belongsTo(InventoryLoanItem::class, 'loan_item_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
