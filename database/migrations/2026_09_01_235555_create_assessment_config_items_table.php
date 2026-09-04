<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'assessment_config_items',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('assessment_config_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('assessment_factor_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table->decimal(
                    'weight',
                    5,
                    2
                );

                $table->unsignedInteger('sort_order')
                    ->default(0);

                $table->timestamps();

                $table->unique(
                    [
                        'assessment_config_id',
                        'assessment_factor_id',
                    ],
                    'assessment_config_factor_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'assessment_config_items'
        );
    }
};
