<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Global human identity shared by school-specific profiles and optional login accounts.
 *
 * This model is intentionally not school-scoped. School-specific identifiers,
 * membership, contact details and academic records remain on their existing models.
 */
class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gender',
        'birth_place',
        'birth_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function coachProfiles(): HasMany
    {
        return $this->hasMany(Coach::class);
    }
}
