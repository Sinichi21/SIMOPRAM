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
        Schema::table('school_user_memberships', function (Blueprint $table): void {
            $table->string('exit_reason', 30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_user_memberships', function (Blueprint $table): void {
            $table->dropColumn('exit_reason');
        });
    }
};
