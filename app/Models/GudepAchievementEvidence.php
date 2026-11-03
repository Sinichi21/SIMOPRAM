<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $scout_group_id
 * @property int $gudep_achievement_id
 * @property string $original_filename
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $sha256
 */
class GudepAchievementEvidence extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'gudep_achievement_id',
        'uploaded_by', 'original_filename', 'disk', 'path',
        'mime_type', 'size_bytes', 'sha256',
    ];

    /** @return BelongsTo<GudepAchievement, $this> */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(GudepAchievement::class, 'gudep_achievement_id');
    }
}
