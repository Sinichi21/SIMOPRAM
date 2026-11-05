<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudep_cash_inventory_receipts', function (Blueprint $table): void {
            $table->uuid('first_batch_token')->nullable()->unique('gcir_first_token_unique');
        });

        Schema::table('gudep_cash_inventory_receipt_items', function (Blueprint $table): void {
            $table->uuid('receipt_batch_token')->nullable();
            $table->unsignedTinyInteger('batch_line_no')->nullable();
            $table->unique(
                ['gudep_cash_inventory_receipt_id', 'receipt_batch_token', 'batch_line_no'],
                'gcir_batch_line_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('gudep_cash_inventory_receipt_items', function (Blueprint $table): void {
            $table->dropUnique('gcir_batch_line_unique');
            $table->dropColumn(['receipt_batch_token', 'batch_line_no']);
        });

        Schema::table('gudep_cash_inventory_receipts', function (Blueprint $table): void {
            $table->dropUnique('gcir_first_token_unique');
            $table->dropColumn('first_batch_token');
        });
    }
};
