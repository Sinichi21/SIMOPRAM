<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_channels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | telegram
            | whatsapp
            |--------------------------------------------------------------------------
            */
            $table->string('channel', 30);

            /*
            |--------------------------------------------------------------------------
            | Telegram = chat_id
            | WhatsApp = nomor telepon
            |--------------------------------------------------------------------------
            */
            $table->string('destination', 255);

            $table->boolean('is_verified')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->json('metadata')
                ->nullable();

            $table->timestamp('verified_at')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['school_id', 'user_id', 'channel'],
                'user_notification_channel_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'user_notification_channels'
        );
    }
};