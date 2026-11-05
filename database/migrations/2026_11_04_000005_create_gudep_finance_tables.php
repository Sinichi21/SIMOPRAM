<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_finance_budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->string('category', 100);
            $table->unsignedBigInteger('allocated_amount');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'scout_group_id', 'academic_year_id', 'category'], 'gudep_budget_unique');
        });

        Schema::create('gudep_cash_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('direction', 10);
            $table->string('category', 100);
            $table->date('occurred_at');
            $table->string('description', 255);
            $table->unsignedBigInteger('amount');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 15)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'scout_group_id', 'academic_year_id', 'status', 'occurred_at'], 'gudep_cash_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_cash_entries');
        Schema::dropIfExists('gudep_finance_budgets');
    }
};
