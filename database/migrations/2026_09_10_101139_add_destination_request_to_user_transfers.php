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
        Schema::table('user_transfers', function (Blueprint $table): void {
            $table->boolean('initiated_by_destination')->default(false);
            $table->foreignId('target_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('target_classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_transfers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('target_year_id');
            $table->dropConstrainedForeignId('target_classroom_id');
            $table->dropColumn('initiated_by_destination');
        });
    }
};
