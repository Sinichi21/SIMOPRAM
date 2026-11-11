<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudepRkagDecision extends Model
{
    use BelongsToSchool;

    public $timestamps = true;

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id', 'gudep_program_sheet_id',
        'actor_id', 'action', 'snapshot_hash', 'notes',
    ];
}
