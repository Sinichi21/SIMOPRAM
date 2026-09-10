<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemesterGradeSnapshot extends Model
{
    use BelongsToSchool;

    /**
     * Tabel semester_grade_snapshots hanya memiliki created_at,
     * tanpa updated_at. Snapshot bersifat immutable setelah dibuat,
     * sehingga timestamp otomatis Eloquent dinonaktifkan.
     *
     * created_at diisi eksplisit oleh SemesterClosureService.
     */
    public $timestamps = false;

    protected $fillable = [
        'semester_closure_id',
        'student_id',
        'student_nis',
        'student_name',
        'classroom_id',
        'classroom_name',
        'final_score',
        'letter_grade',
        'description',
        'factor_scores',
        'config_signature',
        'attendance_source_version',
        'source_calculated_at',
        'record_hash',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'factor_scores' => 'array',
            'attendance_source_version' => 'integer',
            'source_calculated_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function closure(): BelongsTo
    {
        return $this->belongsTo(
            SemesterClosure::class,
            'semester_closure_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(
            Classroom::class
        );
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class
        );
    }
}
