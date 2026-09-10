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
        Schema::table('user_transfers', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreignId('student_id')->nullable()->constrained('students')->restrictOnDelete();
            $table->foreignId('target_student_id')->nullable()->constrained('students')->restrictOnDelete();
            $table->index(['student_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('user_transfers')->whereNull('user_id')->exists()) {
            throw new RuntimeException('Cannot roll back while student transfers without accounts exist.');
        }
        Schema::table('user_transfers', function (Blueprint $table): void {
            $table->dropIndex(['student_id', 'status']);
            $table->dropConstrainedForeignId('student_id');
            $table->dropConstrainedForeignId('target_student_id');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
