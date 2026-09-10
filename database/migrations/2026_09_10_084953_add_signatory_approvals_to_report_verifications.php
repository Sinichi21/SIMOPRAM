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
        Schema::table('report_verifications', function (Blueprint $table): void {
            $table->json('required_signatories')->nullable();
            $table->json('required_signatory_ids')->nullable();
            $table->json('signatory_approvals')->nullable();
            $table->timestamp('approval_completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_verifications', function (Blueprint $table): void {
            $table->dropColumn(['required_signatories', 'required_signatory_ids', 'signatory_approvals', 'approval_completed_at']);
        });
    }
};
