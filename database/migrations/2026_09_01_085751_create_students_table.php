<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('nis', 50)
                ->nullable();

            $table->string('nisn', 30)
                ->nullable();

            $table->string('name', 150);

            $table->char('gender', 1)
                ->nullable();

            $table->string('birth_place', 100)
                ->nullable();

            $table->date('birth_date')
                ->nullable();

            $table->string('phone', 30)
                ->nullable();

            $table->string('parent_phone', 30)
                ->nullable();

            $table->text('address')
                ->nullable();

            $table->string('photo')
                ->nullable();

            $table->date('joined_at')
                ->nullable();

            $table->string('status', 30)
                ->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'school_id',
                'nis',
            ]);

            $table->unique([
                'school_id',
                'nisn',
            ]);

            $table->index([
                'school_id',
                'status',
            ]);

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
