<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('attendance_session_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('activity_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->restrictOnDelete();

            /*
            | present
            | late
            | sick
            | excused
            | absent
            */
            $table->string('status', 20);

            /*
            | manual
            | gps
            | system
            */
            $table->string('source', 20);

            $table->dateTime('checked_in_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | GPS Evidence
            |--------------------------------------------------------------------------
            */

            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->decimal('accuracy_m', 10, 2)
                ->nullable();

            $table->decimal('distance_m', 10, 2)
                ->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'attendance_session_id',
                    'student_id',
                ],
                'att_session_student_unique'
            );

            $table->index(
                ['school_id', 'activity_id'],
                'att_school_activity_idx'
            );

            $table->index(
                ['school_id', 'student_id'],
                'att_school_student_idx'
            );

            $table->index(
                ['attendance_session_id', 'status'],
                'att_session_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};