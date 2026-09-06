<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assessment_configs', function (Blueprint $table) {
            $table->foreignId('participation_factor_id')->nullable()->constrained('assessment_factors')->restrictOnDelete();
            $table->decimal('participation_target_points', 10, 2)->nullable();
        });
        Schema::table('activity_assessments', function (Blueprint $table) {
            $table->boolean('is_special')->default(false);
            $table->unsignedBigInteger('assessment_factor_id')->nullable()->change();
        });
        Schema::create('activity_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_config_id')->constrained()->restrictOnDelete();
            $table->foreignId('activity_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->decimal('points', 10, 2);
            $table->string('notes', 1000)->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['assessment_config_id', 'activity_id', 'student_id'], 'participation_config_activity_student_unique');
        });
        Schema::create('activity_judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_assessment_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->json('scores')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_judges');
        Schema::dropIfExists('activity_participations');
        Schema::table('activity_assessments', function (Blueprint $table) {
            $table->dropColumn('is_special');
        });
        Schema::table('assessment_configs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('participation_factor_id');
            $table->dropColumn('participation_target_points');
        });
    }
};
