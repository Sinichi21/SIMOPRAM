<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_factors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 100);

            $table->string('code', 50);

            $table->text('description')
                ->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['school_id', 'code'],
                'assessment_factor_school_code_unique'
            );

            $table->index(
                ['school_id', 'is_active'],
                'assessment_factor_school_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'assessment_factors'
        );
    }
};