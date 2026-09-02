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
        if (Schema::hasColumn('user_notification_channels', 'channel')) {
            return;
        }

        Schema::table('user_notification_channels', function (Blueprint $table) {
            $table->foreignId('school_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->after('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('channel', 30)
                ->after('user_id');

            $table->string('destination', 255)
                ->after('channel');

            $table->boolean('is_verified')
                ->default(false)
                ->after('destination');

            $table->boolean('is_active')
                ->default(true)
                ->after('is_verified');

            $table->json('metadata')
                ->nullable()
                ->after('is_active');

            $table->timestamp('verified_at')
                ->nullable()
                ->after('metadata');

            $table->unique(
                ['school_id', 'user_id', 'channel'],
                'user_notification_channel_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
