<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_link_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Token RAW tidak disimpan.
            | Kita hanya menyimpan SHA-256 hash.
            |--------------------------------------------------------------------------
            */

            $table->string(
                'token_hash',
                64
            )->unique();

            $table->timestamp(
                'expires_at'
            );

            $table->timestamp(
                'used_at'
            )->nullable();

            $table->timestamps();

            $table->index(
                [
                    'school_id',
                    'user_id',
                ],
                'telegram_link_school_user_idx'
            );

            $table->index(
                [
                    'expires_at',
                    'used_at',
                ],
                'telegram_link_expiry_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'telegram_link_tokens'
        );
    }
};