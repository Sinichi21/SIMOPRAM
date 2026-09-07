<?php

namespace App\Models;

use Database\Factories\MessagingSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array{token?: string, username?: string, host?: string, port?: string|int, scheme?: string, password?: string, from_address?: string, from_name?: string}|null $options
 */
class MessagingSetting extends Model
{
    /** @use HasFactory<MessagingSettingFactory> */
    use HasFactory;

    protected $fillable = ['channel', 'enabled', 'options'];

    protected $hidden = ['options'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'options' => 'encrypted:array'];
    }

    public static function forChannel(string $channel): ?self
    {
        return static::query()->where('channel', $channel)->first();
    }
}
