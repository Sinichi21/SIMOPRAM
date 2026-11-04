<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GudepCashPeriod extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id',
        'opening_balance', 'opening_set_by', 'opening_set_at',
        'status', 'closing_balance', 'closing_checksum', 'closed_by',
        'closed_at', 'closing_notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'integer',
            'closing_balance' => 'integer',
            'opening_set_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
