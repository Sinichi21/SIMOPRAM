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
            $table->foreignId('gudep_formation_id')->nullable()->after('scout_level_id')
                ->constrained('gudep_formations')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gudep_program_sheets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('gudep_formation_id');
        });
    }
};
