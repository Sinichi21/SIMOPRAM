<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GudepCashCloseDecision extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id',
        'gudep_cash_period_id', 'action', 'actor_id', 'requester_id',
        'ledger_checksum', 'book_balance', 'notes', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'book_balance' => 'integer',
            'decided_at' => 'datetime',
        ];
    }
}
