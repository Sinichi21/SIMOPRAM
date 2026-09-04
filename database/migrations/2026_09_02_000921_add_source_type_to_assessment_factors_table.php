<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_factors', function (Blueprint $table) {
            $table->string('source_type', 20)
                ->default('manual')
                ->after('description');

            $table->index(
                ['school_id', 'source_type'],
                'assessment_factor_source_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('assessment_factors', function (Blueprint $table) {
            $table->dropIndex(
                'assessment_factor_source_idx'
            );

            $table->dropColumn(
                'source_type'
            );
        });
    }
};
