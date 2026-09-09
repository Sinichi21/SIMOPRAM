<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'registration_data')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->json('registration_data')->nullable()->after('requested_role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'registration_data')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('registration_data');
            });
        }
    }
};
