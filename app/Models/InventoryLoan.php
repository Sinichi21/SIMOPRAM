<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLoan extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'loan_number', 'borrower_type', 'borrower_user_id', 'borrower_scout_group_id',
        'borrower_name', 'borrower_organization', 'borrower_contact', 'requested_by',
        'approved_by', 'requested_at', 'approved_at', 'actual_handover_at',
        'actual_return_at', 'status', 'purpose', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'actual_handover_at' => 'datetime',
            'actual_return_at' => 'datetime',
        ];
    }

    public function borrowerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_user_id');
    }

    public function borrowerScoutGroup(): BelongsTo
    {
        return $this->belongsTo(ScoutGroup::class, 'borrower_scout_group_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryLoanItem::class, 'loan_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InventoryLoanEvent::class, 'loan_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InventoryLoanDocument::class, 'loan_id');
    }
}
