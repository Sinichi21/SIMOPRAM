<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('activity_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('attendance_session_id')
                ->nullable()
                ->constrained('attendance_sessions')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('objective')
                ->nullable();

            $table->text('material')
                ->nullable();

            $table->text('activity_description');

            $table->text('result')
                ->nullable();

            $table->text('evaluation')
                ->nullable();

            $table->text('follow_up')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->string('status', 20)
                ->default('draft');

            $table->timestamp('published_at')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['school_id', 'activity_id'],
                'journals_school_activity_unique'
            );

            $table->index(
                ['school_id', 'status'],
                'journals_school_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};