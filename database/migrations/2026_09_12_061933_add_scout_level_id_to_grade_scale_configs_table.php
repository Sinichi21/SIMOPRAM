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
        Schema::table('grade_scale_configs', function (Blueprint $table) {
            $table->foreignId('scout_level_id')->nullable()->constrained('scout_levels')->restrictOnDelete();
            $table->index(['school_id', 'scout_level_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grade_scale_configs', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'scout_level_id', 'is_active']);
            $table->dropConstrainedForeignId('scout_level_id');
        });
    }
};
