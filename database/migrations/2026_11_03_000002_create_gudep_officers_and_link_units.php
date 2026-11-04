<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scout_units', function (Blueprint $table): void {
            // Existing units stay unassigned until a school administrator links them.
            $table->foreignId('scout_group_id')->nullable()
                ->constrained('scout_groups')->nullOnDelete();
            $table->index(['school_id', 'scout_group_id'], 'scout_units_school_gudep_idx');
        });

        Schema::create('gudep_officers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('coaches')->nullOnDelete();
            $table->string('scope', 20); // gudep, mabi, or satuan
            $table->string('position', 150);
            $table->string('person_name', 150);
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->string('decree_number', 180)->nullable();
            $table->date('decree_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'scout_group_id', 'scope'], 'gudep_officers_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_officers');
        Schema::table('scout_units', function (Blueprint $table): void {
            $table->dropIndex('scout_units_school_gudep_idx');
            $table->dropConstrainedForeignId('scout_group_id');
        });
    }
};
