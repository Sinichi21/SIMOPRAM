<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudep_cash_entries', function (Blueprint $table): void {
            $table->foreignId('reversal_of_id')->nullable()
                ->unique('gudep_cash_reversal_unique')
                ->constrained('gudep_cash_entries')->restrictOnDelete();
            $table->string('reversal_reason', 1000)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gudep_cash_entries', function (Blueprint $table): void {
            $table->dropForeign(['reversal_of_id']);
            $table->dropUnique('gudep_cash_reversal_unique');
            $table->dropColumn(['reversal_of_id', 'reversal_reason']);
        });
    }
};
