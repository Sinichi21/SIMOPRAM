<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_scout_level', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('scout_level_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'activity_id',
                'scout_level_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_scout_level');
    }
};
