<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'attendance_score_settings',
            function (Blueprint $table): void {
                $table
                    ->unsignedBigInteger('version')
                    ->default(1)
                    ->after('absent_weight');
            }
        );

        Schema::table(
            'student_scores',
            function (Blueprint $table): void {
                $table
                    ->unsignedBigInteger('source_version')
                    ->nullable()
                    ->after('source');

                $table
                    ->timestamp('source_synced_at')
                    ->nullable()
                    ->after('source_version');

                $table->index(
                    [
                        'assessment_config_id',
                        'source',
                        'source_version',
                    ],
                    'student_scores_source_ver_idx'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'student_scores',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'student_scores_source_ver_idx'
                );

                $table->dropColumn([
                    'source_version',
                    'source_synced_at',
                ]);
            }
        );

        Schema::table(
            'attendance_score_settings',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'version'
                );
            }
        );
    }
};