<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_scout_levels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('scout_level_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('started_at')
                ->nullable();

            $table->date('ended_at')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique(
                [
                'student_id',
                'academic_year_id',
                'scout_level_id',
            ],
                'student_scout_levels_student_year_level_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_scout_levels');
    }
};