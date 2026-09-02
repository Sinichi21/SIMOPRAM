<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'attendance_score_settings',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Bobot dalam persen
                |--------------------------------------------------------------------------
                |
                | 100 = nilai penuh
                | 75  = 75%
                | 0   = tidak mendapatkan nilai
                |
                */

                $table
                    ->decimal(
                        'present_weight',
                        5,
                        2
                    )
                    ->default(100);

                $table
                    ->decimal(
                        'late_weight',
                        5,
                        2
                    )
                    ->default(75);

                $table
                    ->decimal(
                        'sick_weight',
                        5,
                        2
                    )
                    ->default(75);

                $table
                    ->decimal(
                        'excused_weight',
                        5,
                        2
                    )
                    ->default(75);

                $table
                    ->decimal(
                        'absent_weight',
                        5,
                        2
                    )
                    ->default(0);

                $table
                    ->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Satu konfigurasi per sekolah
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    'school_id',
                    'attendance_score_settings_school_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'attendance_score_settings'
        );
    }
};
