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
            // A sheet references the existing activity; it does not duplicate its data.
            $table->foreignId('activity_id')->nullable()->after('parent_id')
                ->constrained('activities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gudep_program_sheets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('activity_id');
        });
    }
};
