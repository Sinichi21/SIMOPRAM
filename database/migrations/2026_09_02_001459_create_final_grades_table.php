<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_grades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId(
                'assessment_config_id'
            )
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal(
                'final_score',
                5,
                2
            );

            $table->string(
                'letter_grade',
                10
            )->nullable();

            $table->text(
                'description'
            )->nullable();

            $table->timestamp(
                'calculated_at'
            );

            $table->foreignId(
                'calculated_by'
            )
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                [
                    'assessment_config_id',
                    'student_id',
                ],
                'final_grade_config_student_unique'
            );

            $table->index(
                ['school_id', 'student_id'],
                'final_grade_school_student_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'final_grades'
        );
    }
};