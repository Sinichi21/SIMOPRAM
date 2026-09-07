<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_document_settings', function (Blueprint $table): void {
            $table->string('coordinator_name', 150)
                ->nullable()
                ->after('principal_nip');

            $table->string('coordinator_nip', 50)
                ->nullable()
                ->after('coordinator_name');

            $table->string('parent_agency', 200)
                ->nullable()
                ->after('signing_city');

            $table->unsignedTinyInteger('extracurricular_weekday')
                ->nullable()
                ->after('parent_agency');

            $table->time('extracurricular_start_time')
                ->nullable()
                ->after('extracurricular_weekday');

            $table->time('extracurricular_end_time')
                ->nullable()
                ->after('extracurricular_start_time');

            $table->string('extracurricular_location', 255)
                ->nullable()
                ->after('extracurricular_end_time');
        });
    }

    public function down(): void
    {
        Schema::table('school_document_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'coordinator_name',
                'coordinator_nip',
                'parent_agency',
                'extracurricular_weekday',
                'extracurricular_start_time',
                'extracurricular_end_time',
                'extracurricular_location',
            ]);
        });
    }
};
