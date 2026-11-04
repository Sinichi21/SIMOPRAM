<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            // NTA is a scout identifier; NIS/NISN remain school identifiers.
            // Nullable allows existing students without issued NTA to remain valid.
            $table->string('nta', 100)->nullable()->after('nisn');
            $table->unique(['school_id', 'nta'], 'students_school_nta_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique('students_school_nta_unique');
            $table->dropColumn('nta');
        });
    }
};
