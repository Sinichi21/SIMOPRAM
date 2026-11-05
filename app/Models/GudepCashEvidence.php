<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $scout_group_id
 * @property int $gudep_cash_entry_id
 * @property string $original_filename
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $sha256
 */
class GudepCashEvidence extends Model
{
    use BelongsToSchool;

    protected $table = 'gudep_cash_evidences';

    protected $fillable = [
        'school_id', 'scout_group_id', 'gudep_cash_entry_id', 'uploaded_by',
        'original_filename', 'disk', 'path', 'mime_type', 'size_bytes', 'sha256',
    ];

    /** @return BelongsTo<GudepCashEntry, $this> */
    public function cashEntry(): BelongsTo
    {
        return $this->belongsTo(GudepCashEntry::class, 'gudep_cash_entry_id');
    }
}
