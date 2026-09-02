<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'attendance_session_participants',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('attendance_session_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('student_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'attendance_session_id',
                        'student_id',
                    ],
                    'att_participant_session_student_unique'
                );

                $table->index(
                    ['school_id', 'attendance_session_id'],
                    'att_participant_school_session_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'attendance_session_participants'
        );
    }
};