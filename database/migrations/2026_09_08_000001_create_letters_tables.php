<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 20);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'direction', 'year'], 'letter_seq_school_direction_year_unique');
        });

        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 20);
            $table->string('agenda_number', 100)->nullable();
            $table->string('letter_number', 150)->nullable();
            $table->date('letter_date');
            $table->date('received_date')->nullable();
            $table->string('sender', 200)->nullable();
            $table->string('recipient', 200)->nullable();
            $table->string('subject', 255);
            $table->string('classification', 100)->nullable();
            $table->longText('body')->nullable();
            $table->string('signatory_name', 150)->nullable();
            $table->string('signatory_position', 150)->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'direction', 'status'], 'letters_school_direction_status_idx');
            $table->index(['school_id', 'letter_date'], 'letters_school_date_idx');
            $table->index('letter_number');
            $table->index('agenda_number');
            $table->index('subject');
        });

        Schema::create('letter_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 30)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_attachments');
        Schema::dropIfExists('letters');
        Schema::dropIfExists('letter_number_sequences');
    }
};
