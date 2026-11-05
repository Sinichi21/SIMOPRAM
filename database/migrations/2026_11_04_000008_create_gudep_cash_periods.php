<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_cash_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->unsignedBigInteger('opening_balance')->default(0);
            $table->foreignId('opening_set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('opening_set_at')->nullable();
            $table->string('status', 15)->default('open');
            $table->bigInteger('closing_balance')->nullable();
            $table->char('closing_checksum', 64)->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'scout_group_id', 'academic_year_id'], 'gudep_cash_period_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_cash_periods');
    }
};
