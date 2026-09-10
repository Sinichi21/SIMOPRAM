<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignatoryProfile extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'user_id',
        'position',
        'identifier_type',
        'identifier_number',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function identity(): string
    {
        $number = trim((string) $this->identifier_number);

        if ($number === '') {
            return '';
        }

        $type = strtoupper(trim((string) $this->identifier_type));

        return $type !== '' ? $type.'. '.$number : $number;
    }
}
