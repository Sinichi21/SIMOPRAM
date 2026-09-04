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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('requested_school_id')
                ->nullable()
                ->after('system_role')
                ->constrained('schools')
                ->nullOnDelete();
            $table->string('requested_role', 30)
                ->nullable()
                ->after('requested_school_id');
            $table->string('approval_status', 20)
                ->default('approved')
                ->after('requested_role')
                ->index();
            $table->foreignId('approved_by')
                ->nullable()
                ->after('approval_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')
                ->nullable()
                ->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_school_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'requested_role',
                'approval_status',
                'approved_at',
            ]);
        });
    }
};
