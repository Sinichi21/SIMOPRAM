<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('final_grades', function (Blueprint $table) {
            $table->timestamp('calculated_at')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        /*
         * Jangan langsung mengubah kembali menjadi NOT NULL
         * selama kemungkinan terdapat row invalid dengan calculated_at = NULL.
         */
    }
};
