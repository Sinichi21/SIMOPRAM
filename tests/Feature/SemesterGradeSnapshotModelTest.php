<?php

use App\Models\SemesterGradeSnapshot;

test('semester grade snapshot exposes all fields used by semester closure service', function (): void {
    $model = new SemesterGradeSnapshot();

    expect($model->getFillable())->toContain(
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
    );

    expect($model->usesTimestamps())->toBeFalse();
});

test('semester grade snapshot casts json and date fields', function (): void {
    $model = new SemesterGradeSnapshot();

    expect($model->getCasts())
        ->toMatchArray([
            'final_score' => 'decimal:2',
            'factor_scores' => 'array',
            'attendance_source_version' => 'integer',
            'source_calculated_at' => 'datetime',
            'created_at' => 'datetime',
        ]);
});
