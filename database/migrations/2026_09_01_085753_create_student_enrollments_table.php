<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('classroom_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status', 30)
                ->default('active');

            $table->date('enrolled_at')
                ->nullable();

            $table->date('completed_at')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'student_id',
                    'academic_year_id',
                ],
                'student_enrollments_student_year_unique'

            );

            $table->index(
                [
                    'school_id',
                    'academic_year_id',
                    'classroom_id',
                ],
                'student_enrollments_school_year_class_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
