<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_verifications', function (Blueprint $table): void {
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('replaces_id')->nullable()
                ->constrained('report_verifications')->restrictOnDelete();
            $table->foreignId('superseded_by_id')->nullable()
                ->constrained('report_verifications')->restrictOnDelete();
            $table->index(
                ['school_id', 'source_type', 'source_id', 'document_type', 'revision'],
                'rv_revision_family_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('report_verifications', function (Blueprint $table): void {
            $table->dropIndex('rv_revision_family_idx');
            $table->dropConstrainedForeignId('superseded_by_id');
            $table->dropConstrainedForeignId('replaces_id');
            $table->dropColumn('revision');
        });
    }
};
