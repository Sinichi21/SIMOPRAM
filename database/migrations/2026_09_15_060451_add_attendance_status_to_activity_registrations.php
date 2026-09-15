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
        Schema::table('activity_registrations', function (Blueprint $table): void {
            $table->string('attendance_status', 20)->nullable();
            $table->foreignId('attendance_marked_by')->nullable()->constrained('users')->nullOnDelete();
        });
        DB::table('activity_registrations')->whereNotNull('checked_in_at')->update(['attendance_status' => 'present']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_registrations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('attendance_marked_by');
            $table->dropColumn('attendance_status');
        });
    }
};
