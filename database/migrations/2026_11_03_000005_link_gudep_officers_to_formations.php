<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudep_officers', function (Blueprint $table): void {
            $table->foreignId('gudep_formation_id')->nullable()
                ->constrained('gudep_formations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gudep_officers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('gudep_formation_id');
        });
    }
};
