<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | attendance_source_version
        |--------------------------------------------------------------------------
        */

        if (
            ! Schema::hasColumn(
                'final_grades',
                'attendance_source_version'
            )
        ) {
            Schema::table(
                'final_grades',
                function (Blueprint $table): void {
                    $table
                        ->unsignedBigInteger(
                            'attendance_source_version'
                        )
                        ->nullable()
                        ->after(
                            'final_score'
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | calculated_at
        |--------------------------------------------------------------------------
        */

        if (
            ! Schema::hasColumn(
                'final_grades',
                'calculated_at'
            )
        ) {
            Schema::table(
                'final_grades',
                function (Blueprint $table): void {
                    $table
                        ->timestamp(
                            'calculated_at'
                        )
                        ->nullable()
                        ->after(
                            'attendance_source_version'
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | calculated_by
        |--------------------------------------------------------------------------
        */

        if (
            ! Schema::hasColumn(
                'final_grades',
                'calculated_by'
            )
        ) {
            Schema::table(
                'final_grades',
                function (Blueprint $table): void {
                    $table
                        ->foreignId(
                            'calculated_by'
                        )
                        ->nullable()
                        ->after(
                            'calculated_at'
                        )
                        ->constrained(
                            'users'
                        )
                        ->nullOnDelete();
                }
            );
        }
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | calculated_by
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'final_grades',
                'calculated_by'
            )
        ) {
            Schema::table(
                'final_grades',
                function (Blueprint $table): void {
                    $table->dropForeign([
                        'calculated_by',
                    ]);

                    $table->dropColumn(
                        'calculated_by'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | calculated_at
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'final_grades',
                'calculated_at'
            )
        ) {
            Schema::table(
                'final_grades',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'calculated_at'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | attendance_source_version
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'final_grades',
                'attendance_source_version'
            )
        ) {
            Schema::table(
                'final_grades',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'attendance_source_version'
                    );
                }
            );
        }
    }
};
