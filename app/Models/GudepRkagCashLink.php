<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudepRkagCashLink extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id', 'gudep_program_sheet_id',
        'gudep_cash_entry_id', 'linked_by', 'program_entry_key', 'rkag_line_index',
        'approved_snapshot_hash', 'line_fingerprint',
    ];

    public function cashEntry(): BelongsTo
    {
        return $this->belongsTo(GudepCashEntry::class, 'gudep_cash_entry_id');
    }

    public function programSheet(): BelongsTo
    {
        return $this->belongsTo(GudepProgramSheet::class, 'gudep_program_sheet_id');
    }
}
