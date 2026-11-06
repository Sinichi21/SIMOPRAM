<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudep_program_sheets', function (Blueprint $table): void {
            $table->string('letterhead_type', 12)->nullable()->after('scout_level_id');
        });
    }

    public function down(): void
    {
        Schema::table('gudep_program_sheets', function (Blueprint $table): void {
            $table->dropColumn('letterhead_type');
        });
    }
};
