<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_accreditation_indicators', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('scout_level_id')->nullable()->constrained('scout_levels')->restrictOnDelete();
            $table->string('code', 80);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'scout_group_id', 'academic_year_id'], 'gai_scope_idx');
            $table->unique(['school_id', 'scout_group_id', 'academic_year_id', 'scout_level_id', 'code'], 'gai_scope_level_code');
        });

        Schema::create('gudep_accreditation_evidence_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('indicator_id')->constrained('gudep_accreditation_indicators')->cascadeOnDelete();
            $table->string('source_kind', 40);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['indicator_id', 'source_kind', 'source_id'], 'gael_indicator_source_unique');
            $table->index(['school_id', 'source_kind', 'source_id'], 'gael_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_accreditation_evidence_links');
        Schema::dropIfExists('gudep_accreditation_indicators');
    }
};
