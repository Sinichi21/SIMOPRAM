<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('assessment_config_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('assessment_factor_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal(
                'score',
                5,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | manual
            | attendance
            | system
            |--------------------------------------------------------------------------
            */
            $table->string(
                'source',
                20
            );

            $table->foreignId('entered_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'assessment_config_id',
                    'student_id',
                    'assessment_factor_id',
                ],
                'student_score_config_student_factor_unique'
            );

            $table->index(
                ['school_id', 'student_id'],
                'student_score_school_student_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'student_scores'
        );
    }
};