<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_loan_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_id')->constrained('inventory_loans')->restrictOnDelete();
            $table->foreignId('loan_event_id')->constrained('inventory_loan_events')->restrictOnDelete();
            $table->string('disk', 30)->default('local');
            $table->string('path', 500);
            $table->string('mime_type', 60);
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'loan_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_loan_evidence');
    }
};
