<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $scout_group_id
 * @property int $gudep_officer_id
 * @property string $path
 * @property string $sha256
 * @property string $disk
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size_bytes
 */
class GudepOfficerDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'gudep_officer_id', 'original_filename',
        'disk', 'path', 'mime_type', 'size_bytes', 'sha256', 'uploaded_by',
    ];

    /**
     * @return BelongsTo<GudepOfficer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(GudepOfficer::class, 'gudep_officer_id');
    }
}
