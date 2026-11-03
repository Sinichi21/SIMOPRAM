<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_work_programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('gudep_formation_id')->nullable()
                ->constrained('gudep_formations')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('title', 200);
            $table->text('objective')->nullable();
            $table->date('planned_for')->nullable();
            $table->string('status', 20)->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'scout_group_id', 'academic_year_id'], 'gudep_program_scope_idx');
        });

        Schema::create('gudep_competency_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('scout_level_id')->constrained('scout_levels')->restrictOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('coaches')->nullOnDelete();
            $table->string('category', 3); // SKU or SKK; TKK is an award, not a separate exam
            $table->string('requirement_code', 80);
            $table->string('requirement_name', 255);
            $table->string('status', 20)->default('in_progress');
            $table->date('assessed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'scout_group_id', 'academic_year_id', 'student_id', 'category', 'requirement_code'], 'gudep_competency_unique');
        });

        Schema::create('gudep_achievements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('kind', 30); // achievement, tkk_award, or development
            $table->string('title', 200);
            $table->date('awarded_at');
            $table->string('issuer', 180)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'scout_group_id', 'academic_year_id', 'awarded_at'], 'gudep_achievements_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_achievements');
        Schema::dropIfExists('gudep_competency_records');
        Schema::dropIfExists('gudep_work_programs');
    }
};
