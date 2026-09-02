<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'attendance_histories',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('attendance_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('changed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('old_status', 20)
                    ->nullable();

                $table->string('new_status', 20);

                $table->string('source', 20);

                $table->text('notes')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    ['attendance_id', 'created_at'],
                    'att_history_attendance_date_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'attendance_histories'
        );
    }
};