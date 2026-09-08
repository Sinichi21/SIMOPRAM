<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 36)->unique();
            $table->foreignId('school_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('email', 150);
            $table->string('category', 60);
            $table->string('subject', 180);
            $table->text('body');
            $table->string('attachment_path')->nullable();
            $table->string('status', 20)->default('new');
            $table->text('response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
