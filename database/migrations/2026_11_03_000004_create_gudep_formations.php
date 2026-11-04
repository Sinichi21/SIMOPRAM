<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_formations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('scout_level_id')->constrained('scout_levels')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('formation_type', 20);
            $table->string('gender', 12)->default('campuran');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['school_id', 'scout_group_id', 'academic_year_id', 'name'],
                'gudep_formations_identity_uq'
            );
        });

        Schema::table('scout_units', function (Blueprint $table): void {
            $table->foreignId('gudep_formation_id')->nullable()
                ->constrained('gudep_formations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scout_units', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('gudep_formation_id');
        });
        Schema::dropIfExists('gudep_formations');
    }
};
