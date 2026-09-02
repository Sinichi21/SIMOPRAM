<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'notification_logs',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('announcement_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | web
                | telegram
                | whatsapp
                |--------------------------------------------------------------------------
                */
                $table->string('channel', 30);

                /*
                |--------------------------------------------------------------------------
                | pending
                | sent
                | failed
                |--------------------------------------------------------------------------
                */
                $table->string('status', 20)
                    ->default('pending');

                $table->string('recipient')
                    ->nullable();

                $table->text('response')
                    ->nullable();

                $table->text('error_message')
                    ->nullable();

                $table->timestamp('sent_at')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'school_id',
                        'channel',
                        'status',
                    ],
                    'notification_log_status_idx'
                );

                $table->index(
                    [
                        'announcement_id',
                        'channel',
                    ],
                    'notification_log_announcement_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'notification_logs'
        );
    }
};