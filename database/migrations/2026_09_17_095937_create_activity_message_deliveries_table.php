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
        Schema::create('activity_message_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_registration_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('channel', 20);
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->index(
                ['activity_registration_id', 'status'],
                'amd_registration_status_idx'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_message_deliveries');
    }
};
