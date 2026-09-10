<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolUserMembership extends Model
{
    protected $attributes = ['is_active' => true];

    protected $fillable = [
        'school_id',
        'user_id',
        'is_active',
        'joined_at',
        'left_at',
        'exit_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'joined_at' => 'date',
            'left_at' => 'date',
        ];
    }

    /** @param array<string, mixed> $options */
    public function save(array $options = []): bool
    {
        return DB::transaction(function () use ($options): bool {
            $user = User::query()->lockForUpdate()->find($this->user_id);
            if ($user?->system_role === 'student' && $this->is_active && $this->left_at === null
                && static::query()->where('user_id', $this->user_id)
                    ->where('school_id', '!=', $this->school_id)
                    ->where('is_active', true)->whereNull('left_at')->exists()) {
                throw ValidationException::withMessages(['membership' => 'Siswa hanya boleh memiliki satu sekolah aktif. Gunakan proses transfer sekolah.']);
            }

            return parent::save($options);
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
