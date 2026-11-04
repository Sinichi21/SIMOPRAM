<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_cash_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('gudep_cash_period_id')->constrained('gudep_cash_periods')->restrictOnDelete();
            $table->bigInteger('book_balance');
            $table->unsignedBigInteger('counted_cash');
            $table->bigInteger('difference');
            $table->char('ledger_checksum', 64);
            $table->text('notes');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('recorded_at');
            $table->timestamps();

            $table->index(['school_id', 'scout_group_id', 'academic_year_id'], 'gudep_cash_reconcile_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_cash_reconciliations');
    }
};
