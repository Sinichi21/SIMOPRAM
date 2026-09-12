<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->index();
            $table->string('log_type', 20)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name', 255);
            $table->string('role', 50)->index();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('school_name', 200)->nullable();
            $table->string('module', 30)->index();
            $table->string('action', 30)->index();
            $table->string('target_type', 100)->nullable();
            $table->string('target_id', 100)->nullable();
            $table->string('description', 500);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 100)->nullable();
            $table->string('status', 20)->index();
            $table->string('request_id', 50)->index();
            $table->timestamp('created_at');
            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
