<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 50);

            $table->unsignedTinyInteger('semester_number');

            $table->date('start_date');
            $table->date('end_date');

            $table->boolean('is_active')
                ->default(false);

            $table->timestamps();

            $table->unique([
                'academic_year_id',
                'semester_number',
            ]);

            $table->index([
                'school_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
