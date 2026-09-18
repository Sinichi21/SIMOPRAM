<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_assessment_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('activity_assessment_id')->constrained()->restrictOnDelete();
            $table->char('code', 48)->unique();
            $table->string('format', 20);
            $table->boolean('with_signatures');
            $table->json('snapshot');
            $table->string('file_path');
            $table->char('file_sha256', 64)->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_assessment_reports');
    }
};
