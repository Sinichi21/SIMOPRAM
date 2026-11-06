<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudep_cash_periods', function (Blueprint $table): void {
            $table->foreignId('close_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('close_requested_at')->nullable();
            $table->text('close_requested_notes')->nullable();
            $table->char('close_requested_checksum', 64)->nullable();
            $table->bigInteger('close_requested_balance')->nullable();
            $table->foreignId('close_requested_reconciliation_id')->nullable()
                ->constrained('gudep_cash_reconciliations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gudep_cash_periods', function (Blueprint $table): void {
            $table->dropForeign(['close_requested_by']);
            $table->dropForeign(['close_requested_reconciliation_id']);
            $table->dropColumn([
                'close_requested_by', 'close_requested_at', 'close_requested_notes',
                'close_requested_checksum', 'close_requested_balance',
                'close_requested_reconciliation_id',
            ]);
        });
    }
};
