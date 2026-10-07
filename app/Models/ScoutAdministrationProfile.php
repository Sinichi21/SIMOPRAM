<?php

namespace App\Models;

use App\Enums\ScoutAdministrationType;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoutAdministrationProfile extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'type',
        'name',
        'number_format',
        'agenda_format',
        'default_signatory_user_id',
        'letterhead_title',
        'letterhead_subtitle',
        'letterhead_address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ScoutAdministrationType::class,
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function defaultSignatory(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_signatory_user_id');
    }
}
