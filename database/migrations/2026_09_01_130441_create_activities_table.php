<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('semester_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title', 200);

            $table->string('activity_type', 50)
                ->default('regular');

            $table->text('description')
                ->nullable();

            $table->string('location', 255)
                ->nullable();

            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->dateTime('start_at');

            $table->dateTime('end_at');

            $table->string('status', 20)
                ->default('draft');

            $table->boolean('is_public')
                ->default(false);

            $table->timestamp('published_at')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['school_id', 'academic_year_id'],
                'activities_school_year_idx'
            );

            $table->index(
                ['school_id', 'start_at'],
                'activities_school_start_idx'
            );

            $table->index(
                ['school_id', 'status'],
                'activities_school_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};