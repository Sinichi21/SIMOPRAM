<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_cash_inventory_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('gudep_cash_entry_id')->constrained(table: 'gudep_cash_entries', indexName: 'gcir_cash_fk')->restrictOnDelete();
            $table->unique('gudep_cash_entry_id', 'gcir_cash_unique');
            $table->foreignId('inventory_stock_movement_id')
                ->constrained(table: 'inventory_stock_movements', indexName: 'gcir_movement_fk')->restrictOnDelete();
            $table->unique('inventory_stock_movement_id', 'gcir_movement_unique');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_cash_inventory_receipts');
    }
};
