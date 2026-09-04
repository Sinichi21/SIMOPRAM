<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title', 200);

            $table->longText('body');

            /*
            |--------------------------------------------------------------------------
            | draft
            | published
            | archived
            |--------------------------------------------------------------------------
            */
            $table->string('status', 20)
                ->default('draft');

            $table->boolean('is_public')
                ->default(false);

            $table->timestamp('publish_at')
                ->nullable();

            $table->timestamp('published_at')
                ->nullable();

            $table->timestamp('expires_at')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['school_id', 'status'],
                'announcements_school_status_idx'
            );

            $table->index(
                ['school_id', 'published_at'],
                'announcements_school_published_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
