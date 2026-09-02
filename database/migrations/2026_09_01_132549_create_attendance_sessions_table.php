<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('activity_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name', 100)
                ->default('Absensi');

            /*
            | all
            | classroom
            | scout_unit
            */
            $table->string('participant_scope', 30)
                ->default('all');

            $table->unsignedBigInteger('participant_scope_id')
                ->nullable();

            $table->dateTime('open_at');

            $table->dateTime('late_after')
                ->nullable();

            $table->dateTime('close_at');

            $table->boolean('allow_manual')
                ->default(true);

            $table->boolean('allow_self_checkin')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | GPS
            |--------------------------------------------------------------------------
            */

            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->unsignedInteger('radius_m')
                ->default(100);

            $table->unsignedInteger('max_accuracy_m')
                ->nullable()
                ->default(100);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['school_id', 'activity_id'],
                'att_sessions_school_activity_idx'
            );

            $table->index(
                ['school_id', 'is_active'],
                'att_sessions_school_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};