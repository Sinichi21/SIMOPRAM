<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudepCashReconciliation extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id', 'gudep_cash_period_id',
        'book_balance', 'counted_cash', 'difference', 'ledger_checksum',
        'notes', 'recorded_by', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'book_balance' => 'integer',
            'counted_cash' => 'integer',
            'difference' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<GudepCashPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(GudepCashPeriod::class, 'gudep_cash_period_id');
    }
}
