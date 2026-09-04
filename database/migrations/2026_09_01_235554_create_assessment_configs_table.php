<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_configs', function (Blueprint $table) {
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

            $table->string('name', 150);

            $table->boolean('is_active')
                ->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                [
                    'school_id',
                    'academic_year_id',
                    'semester_id',
                ],
                'assessment_config_period_idx'
            );

            $table->index(
                [
                    'school_id',
                    'is_active',
                ],
                'assessment_config_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'assessment_configs'
        );
    }
};
