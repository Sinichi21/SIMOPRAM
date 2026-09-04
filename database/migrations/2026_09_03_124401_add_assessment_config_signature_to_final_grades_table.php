<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasColumn(
                'final_grades',
                'assessment_config_signature'
            )
        ) {
            Schema::table(
                'final_grades',
                function (Blueprint $table): void {
                    $table
                        ->string(
                            'assessment_config_signature',
                            64
                        )
                        ->nullable()
                        ->after(
                            'attendance_source_version'
                        );

                    $table->index(
                        'assessment_config_signature',
                        'fg_config_signature_idx'
                    );
                }
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasColumn(
                'final_grades',
                'assessment_config_signature'
            )
        ) {
            Schema::table(
                'final_grades',
                function (Blueprint $table): void {
                    $table->dropIndex(
                        'fg_config_signature_idx'
                    );

                    $table->dropColumn(
                        'assessment_config_signature'
                    );
                }
            );
        }
    }
};
