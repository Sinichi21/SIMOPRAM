<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'journal_attachments',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('journal_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('uploaded_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('original_name');

                $table->string('path');

                $table->string('mime_type', 100)
                    ->nullable();

                $table->unsignedBigInteger('size_bytes')
                    ->default(0);

                $table->timestamps();

                $table->index(
                    ['school_id', 'journal_id'],
                    'journal_attachment_school_journal_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'journal_attachments'
        );
    }
};
