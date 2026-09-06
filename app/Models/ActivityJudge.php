<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\ActivityJudgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityJudge extends Model
{
    use BelongsToSchool;

    /** @use HasFactory<ActivityJudgeFactory> */
    use HasFactory;

    protected $fillable = ['activity_assessment_id', 'name', 'token_hash', 'starts_at', 'expires_at', 'scores', 'finalized_at', 'revoked_at', 'created_by'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'finalized_at' => 'datetime', 'revoked_at' => 'datetime', 'scores' => 'array'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ActivityAssessment::class, 'activity_assessment_id');
    }
}
