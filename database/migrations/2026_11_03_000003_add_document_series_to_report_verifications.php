<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_verifications', function (Blueprint $table): void {
            $table->string('series_key', 100)->default('general');
            $table->index(
                ['school_id', 'source_type', 'source_id', 'document_type', 'series_key'],
                'rv_source_series_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('report_verifications', function (Blueprint $table): void {
            $table->dropIndex('rv_source_series_idx');
            $table->dropColumn('series_key');
        });
    }
};
