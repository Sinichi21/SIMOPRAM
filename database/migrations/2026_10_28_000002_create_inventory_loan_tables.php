<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('loan_number', 40);
            $table->string('borrower_type', 30); // internal | registered_gudep | manual_external
            $table->foreignId('borrower_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('borrower_scout_group_id')->nullable()->constrained('scout_groups')->restrictOnDelete();
            $table->string('borrower_name', 180);
            $table->string('borrower_organization', 180)->nullable();
            $table->string('borrower_contact', 100)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('actual_handover_at')->nullable();
            $table->timestamp('actual_return_at')->nullable();
            $table->string('status', 30)->default('requested');
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'loan_number']);
            $table->index(['school_id', 'status', 'requested_at']);
            $table->index(['school_id', 'borrower_type', 'borrower_scout_group_id']);
        });

        Schema::create('inventory_loan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_id')->constrained('inventory_loans')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('inventory_assets')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('inventory_owners')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('inventory_locations')->restrictOnDelete();
            $table->decimal('quantity_requested', 14, 3);
            $table->decimal('quantity_handed_over', 14, 3)->default(0);
            $table->decimal('quantity_returned', 14, 3)->default(0);
            $table->decimal('quantity_missing', 14, 3)->default(0);
            $table->string('condition_out', 30)->nullable();
            $table->string('condition_in', 30)->nullable();
            $table->text('condition_notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'loan_id']);
            $table->index(['school_id', 'item_id']);
            $table->index(['school_id', 'asset_id']);
        });

        Schema::create('inventory_loan_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_id')->constrained('inventory_loans')->restrictOnDelete();
            $table->foreignId('loan_item_id')->nullable()->constrained('inventory_loan_items')->restrictOnDelete();
            $table->string('event_type', 30); // handover | return | damage | loss | offline_entry
            $table->decimal('quantity', 14, 3)->nullable();
            $table->string('condition', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at'); // actual physical event time
            $table->timestamp('recorded_at'); // time entered into SIMPRAM
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recording_method', 20)->default('online'); // online | offline
            $table->timestamps();

            $table->index(['school_id', 'loan_id', 'event_type', 'occurred_at']);
            $table->index(['school_id', 'recorded_at']);
        });

        Schema::create('inventory_loan_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_id')->constrained('inventory_loans')->restrictOnDelete();
            $table->string('document_type', 20); // handover | return
            $table->unsignedInteger('version');
            $table->string('document_number', 60);
            $table->string('storage_path', 500)->nullable();
            $table->string('sha256', 64)->nullable();
            $table->string('status', 20)->default('draft'); // draft | issued | superseded
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->text('correction_reason')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'document_type', 'document_number']);
            $table->unique(['loan_id', 'document_type', 'version']);
            $table->index(['school_id', 'loan_id', 'document_type']);
        });

        Schema::create('inventory_loan_document_prints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_id')->constrained('inventory_loan_documents')->restrictOnDelete();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('printed_at');
            $table->string('purpose', 30)->default('original'); // original | reprint | corrected_copy
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'document_id', 'printed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_loan_document_prints');
        Schema::dropIfExists('inventory_loan_documents');
        Schema::dropIfExists('inventory_loan_events');
        Schema::dropIfExists('inventory_loan_items');
        Schema::dropIfExists('inventory_loans');
    }
};
