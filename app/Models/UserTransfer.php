<?php

namespace App\Models;

use Database\Factories\UserTransferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTransfer extends Model
{
    /** @use HasFactory<UserTransferFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime', 'initiated_by_destination' => 'boolean', 'previous_target_enrollment' => 'array'];
    }

    public function requestingSchoolId(): int
    {
        return (int) ($this->initiated_by_destination ? $this->to_school_id : $this->from_school_id);
    }

    public function approvingSchoolId(): int
    {
        return (int) ($this->initiated_by_destination ? $this->from_school_id : $this->to_school_id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withoutGlobalScope('school')->withTrashed();
    }

    public function targetYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'target_year_id')->withoutGlobalScope('school');
    }

    public function targetClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'target_classroom_id')->withoutGlobalScope('school');
    }

    public function fromSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'from_school_id');
    }

    public function toSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'to_school_id');
    }
}
