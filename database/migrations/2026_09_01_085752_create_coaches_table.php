<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('nip', 50)
                ->nullable();

            $table->string('name', 150);

            $table->char('gender', 1)
                ->nullable();

            $table->string('phone', 30)
                ->nullable();

            $table->string('position', 100)
                ->nullable();

            $table->string('certificate_number', 100)
                ->nullable();

            $table->string('photo')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'school_id',
                'nip',
            ]);

            $table->index([
                'school_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coaches');
    }
};
