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
        Schema::table('final_grades', function (Blueprint $table) {
            Schema::table('final_grades', function (Blueprint $table): void {
                $table->text('manual_description')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('final_grades', function (Blueprint $table) {
            Schema::table('final_grades', function (Blueprint $table): void {
                $table->dropColumn('manual_description');
            });
        });
    }
};
