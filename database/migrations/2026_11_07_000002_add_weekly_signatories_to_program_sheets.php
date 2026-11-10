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
            $table->string('acknowledged_by_ref', 64)->nullable()->after('letterhead_type');
            $table->string('signed_by_ref', 64)->nullable()->after('acknowledged_by_ref');
        });
    }

    public function down(): void
    {
        Schema::table('gudep_program_sheets', function (Blueprint $table): void {
            $table->dropColumn(['acknowledged_by_ref', 'signed_by_ref']);
        });
    }
};
