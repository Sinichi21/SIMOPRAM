<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_cash_inventory_receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('gudep_cash_inventory_receipt_id')
                ->constrained('gudep_cash_inventory_receipts', 'gcir_items_receipt_fk')->restrictOnDelete();
            $table->foreignId('inventory_stock_movement_id')
                ->constrained('inventory_stock_movements', 'gcir_items_movement_fk')->restrictOnDelete();
            $table->unique('inventory_stock_movement_id', 'gcir_items_movement_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_cash_inventory_receipt_items');
    }
};
