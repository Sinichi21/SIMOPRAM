<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedTinyInteger('routine_session_no')
                ->nullable()
                ->after('activity_type');
        });

        DB::table('activities')
            ->where('activity_type', 'regular')
            ->whereNull('routine_session_no')
            ->update(['routine_session_no' => 1]);
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('routine_session_no');
        });
    }
};
