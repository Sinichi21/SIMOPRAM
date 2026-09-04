<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scout_levels', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)
                ->unique();

            $table->string('name', 100)
                ->unique();

            $table->unsignedTinyInteger('minimum_age')
                ->nullable();

            $table->unsignedTinyInteger('maximum_age')
                ->nullable();

            $table->unsignedTinyInteger('sort_order')
                ->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scout_levels');
    }
};
