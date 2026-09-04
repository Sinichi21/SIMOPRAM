<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scout_unit_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('scout_unit_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('position', 50)
                ->nullable();

            $table->date('joined_at')
                ->nullable();

            $table->date('left_at')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'scout_unit_id',
                'student_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scout_unit_members');
    }
};
