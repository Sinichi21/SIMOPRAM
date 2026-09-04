<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_coach', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('activity_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('coach_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('role', 50)
                ->default('coach');

            $table->timestamps();

            $table->unique(
                ['activity_id', 'coach_id'],
                'activity_coach_unique'
            );

            $table->index(
                ['school_id', 'activity_id'],
                'activity_coach_school_activity_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_coach');
    }
};
