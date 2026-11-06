<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_cash_inventory_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('gudep_cash_inventory_receipt_id')
                ->constrained(table: 'gudep_cash_inventory_receipts', indexName: 'gci_corr_receipt_fk')
                ->restrictOnDelete();
            $table->foreignId('original_stock_movement_id')
                ->constrained(table: 'inventory_stock_movements', indexName: 'gci_corr_original_fk')
                ->restrictOnDelete();
            $table->foreignId('correction_stock_movement_id')
                ->constrained(table: 'inventory_stock_movements', indexName: 'gci_corr_compensate_fk')
                ->restrictOnDelete();
            $table->unique('correction_stock_movement_id', 'gci_corr_movement_unique');
            $table->decimal('quantity', 14, 3);
            $table->string('reason', 1000);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['gudep_cash_inventory_receipt_id', 'original_stock_movement_id'], 'gci_corr_receipt_original_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_cash_inventory_corrections');
    }
};
