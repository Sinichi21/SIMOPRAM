<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'grade_scales',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId(
                    'grade_scale_config_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'letter_grade',
                    10
                );

                $table->decimal(
                    'min_score',
                    5,
                    2
                );

                $table->decimal(
                    'max_score',
                    5,
                    2
                );

                $table->string(
                    'description',
                    255
                )->nullable();

                $table->unsignedInteger(
                    'sort_order'
                )->default(0);

                $table->timestamps();

                $table->index(
                    [
                        'grade_scale_config_id',
                        'min_score',
                        'max_score',
                    ],
                    'grade_scale_score_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'grade_scales'
        );
    }
};