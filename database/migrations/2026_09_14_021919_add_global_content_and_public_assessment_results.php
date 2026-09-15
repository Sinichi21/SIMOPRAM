<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['activities', 'announcements', 'activity_assessments', 'activity_assessment_criteria', 'activity_assessment_targets', 'activity_judges', 'assessment_audit_logs'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->unsignedBigInteger('school_id')->nullable()->change();
            });
        }
        Schema::table('activities', function (Blueprint $table): void {
            $table->unsignedBigInteger('academic_year_id')->nullable()->change();
        });
        Schema::table('activity_assessments', function (Blueprint $table): void {
            $table->timestamp('results_published_at')->nullable();
        });
        Schema::table('activity_assessment_targets', function (Blueprint $table): void {
            $table->string('participant_name', 150)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['activities', 'announcements', 'activity_assessments', 'activity_assessment_criteria', 'activity_assessment_targets', 'activity_judges', 'assessment_audit_logs'] as $name) {
            if (DB::table($name)->whereNull('school_id')->exists()) {
                throw new RuntimeException('Data umum masih tersedia. Gunakan migrasi lanjutan agar data tidak hilang.');
            }
        }
        Schema::table('activity_assessments', fn (Blueprint $table) => $table->dropColumn('results_published_at'));
        Schema::table('activity_assessment_targets', fn (Blueprint $table) => $table->dropColumn('participant_name'));
        Schema::table('activities', fn (Blueprint $table) => $table->unsignedBigInteger('academic_year_id')->nullable(false)->change());
        foreach (['activities', 'announcements', 'activity_assessments', 'activity_assessment_criteria', 'activity_assessment_targets', 'activity_judges', 'assessment_audit_logs'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->unsignedBigInteger('school_id')->nullable(false)->change());
        }
    }
};
