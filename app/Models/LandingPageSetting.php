<?php

namespace App\Models;

use Database\Factories\LandingPageSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingPageSetting extends Model
{
    /** @use HasFactory<LandingPageSettingFactory> */
    use HasFactory;

    protected $fillable = ['key', 'content', 'hero_image'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    /** @return array<string, string> */
    public static function contentForPage(): array
    {
        $defaults = collect(config('landing.fields'))->map(fn (array $field): string => $field['default'])->all();

        return array_replace($defaults, static::query()->where('key', 'global')->value('content') ?? []);
    }
}
