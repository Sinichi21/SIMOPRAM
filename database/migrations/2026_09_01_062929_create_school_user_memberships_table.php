<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_user_memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('is_active')
                ->default(true);

            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();

            $table->timestamps();

            $table->unique([
                'school_id',
                'user_id',
            ]);

            $table->index([
                'user_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_user_memberships');
    }
};
