<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $occurred_at
 * @property CarbonImmutable $created_at
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 */
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime', 'old_values' => 'array', 'new_values' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Log aktivitas tidak dapat diubah.'));
        static::deleting(fn () => throw new \LogicException('Log aktivitas tidak dapat dihapus.'));
    }

    public function localTimestamp(): string
    {
        return $this->occurred_at->setTimezone(config('activity-log.timezone'))->format('d/m/Y H:i:s').' WITA';
    }

    public function summary(): string
    {
        $text = $this->user_name.' '.mb_strtolower($this->description);
        foreach (['score', 'final_score', 'grade', 'letter_grade'] as $field) {
            if (isset($this->old_values[$field], $this->new_values[$field])) {
                $text .= ' dari '.$this->old_values[$field].' menjadi '.$this->new_values[$field];
            }
        }

        return $text.' pada '.$this->localTimestamp();
    }
}
