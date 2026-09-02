<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scout_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 150);

            $table->string('male_number', 50)->nullable();
            $table->string('female_number', 50)->nullable();

            $table->string('kwarran', 150)->nullable();
            $table->string('kwarcab', 150)->nullable();
            $table->string('kwarda', 150)->nullable();

            $table->string('kamabigus_name', 150)->nullable();
            $table->string('head_coach_name', 150)->nullable();

            $table->text('secretariat_address')->nullable();

            $table->date('inauguration_date')->nullable();

            $table->string('logo')->nullable();

            $table->text('description')->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'school_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scout_groups');
    }
};