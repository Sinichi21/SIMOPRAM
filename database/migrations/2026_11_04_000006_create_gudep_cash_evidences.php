<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_cash_evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('gudep_cash_entry_id')->constrained('gudep_cash_entries')->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename', 255);
            $table->string('disk', 30)->default('local');
            $table->string('path', 500)->unique();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->timestamps();
            $table->index(['school_id', 'scout_group_id', 'gudep_cash_entry_id'], 'gudep_cash_evidence_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_cash_evidences');
    }
};
