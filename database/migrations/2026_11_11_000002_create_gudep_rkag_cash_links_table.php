<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_rkag_cash_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('gudep_program_sheet_id')->constrained('gudep_program_sheets')->cascadeOnDelete();
            $table->foreignId('gudep_cash_entry_id')->constrained('gudep_cash_entries')->restrictOnDelete();
            $table->foreignId('linked_by')->constrained('users')->restrictOnDelete();
            $table->string('program_entry_key', 80);
            $table->unsignedSmallInteger('rkag_line_index');
            $table->string('approved_snapshot_hash', 64);
            $table->string('line_fingerprint', 64);
            $table->timestamps();
            $table->unique('gudep_cash_entry_id', 'rkag_cash_link_once_idx');
            $table->index(['gudep_program_sheet_id', 'program_entry_key'], 'rkag_cash_link_plan_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_rkag_cash_links');
    }
};
