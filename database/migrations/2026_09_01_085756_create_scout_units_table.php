<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scout_units', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('scout_level_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 100);

            $table->string('unit_type', 30);

            $table->foreignId('leader_student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();

            $table->text('description')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                [
                'school_id',
                'academic_year_id',
                'scout_level_id',
                'name',
            ],
                'scout_units_school_year_level_name_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scout_units');
    }
};