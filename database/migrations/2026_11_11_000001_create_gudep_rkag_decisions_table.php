<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_rkag_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('gudep_program_sheet_id')->constrained('gudep_program_sheets')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 24);
            $table->string('snapshot_hash', 64);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['gudep_program_sheet_id', 'id'], 'rkag_sheet_decisions_idx');
            $table->index(['school_id', 'scout_group_id', 'academic_year_id'], 'rkag_tenant_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_rkag_decisions');
    }
};
