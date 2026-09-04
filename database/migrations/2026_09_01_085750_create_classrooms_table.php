<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 50);

            $table->unsignedTinyInteger('grade')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'school_id',
                'name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
