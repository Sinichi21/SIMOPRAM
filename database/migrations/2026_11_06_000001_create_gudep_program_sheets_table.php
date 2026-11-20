<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_program_sheets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->restrictOnDelete();
            $table->foreignId('scout_level_id')->nullable()->constrained('scout_levels')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('gudep_program_sheets')->restrictOnDelete();
            $table->string('kind', 12); // annual, semester, weekly
            $table->string('title', 200);
            $table->date('scheduled_for')->nullable();
            $table->json('entries');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'scout_group_id', 'academic_year_id', 'kind'], 'gudep_plan_context_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_program_sheets');
    }
};
