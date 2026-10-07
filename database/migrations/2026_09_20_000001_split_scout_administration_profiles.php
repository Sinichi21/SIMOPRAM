<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scout_administration_profiles', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 20);
            $table->string('name', 120);

            $table->string('number_format', 255)
                ->default(
                    '{sequence}/{type}/{month_roman}.{year_short}/{gudep}-{field}'
                );

            $table->string('agenda_format', 120)
                ->default('{sequence_padded}/SM/{year}');

            $table->foreignId('default_signatory_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('letterhead_title', 255)->nullable();
            $table->string('letterhead_subtitle', 255)->nullable();
            $table->text('letterhead_address')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['school_id', 'type'],
                'scout_admin_profiles_school_type_unique'
            );
        });

        Schema::table('letters', function (Blueprint $table): void {
            $table->string('administration_type', 20)
                ->nullable()
                ->after('direction');

            $table->index(
                ['school_id', 'administration_type', 'direction'],
                'letters_school_admin_direction_idx'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Letter Number Sequences
        |--------------------------------------------------------------------------
        |
        | Index lama:
        |
        | school_id + direction + year
        |
        | sebelumnya juga digunakan MySQL sebagai index pendukung foreign key
        | school_id. Karena itu kita harus menyediakan index school_id sendiri
        | sebelum unique index lama dihapus.
        |
        */

        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->index(
                'school_id',
                'letter_number_sequences_school_id_index'
            );
        });

        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->dropUnique(
                'letter_seq_school_direction_year_unique'
            );
        });

        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->string('administration_type', 20)
                ->default('mabigus')
                ->after('direction');

            $table->unique(
                [
                    'school_id',
                    'direction',
                    'administration_type',
                    'year',
                ],
                'letter_seq_school_direction_admin_year_unique'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Backward Compatibility
        |--------------------------------------------------------------------------
        |
        | Semua surat lama dianggap menggunakan administrasi Mabigus.
        |
        */

        DB::table('letters')
            ->whereNull('administration_type')
            ->update([
                'administration_type' => 'mabigus',
            ]);
    }

    public function down(): void
    {
        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->dropUnique(
                'letter_seq_school_direction_admin_year_unique'
            );
        });

        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->dropColumn('administration_type');

            $table->unique(
                ['school_id', 'direction', 'year'],
                'letter_seq_school_direction_year_unique'
            );
        });

        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->dropIndex(
                'letter_number_sequences_school_id_index'
            );
        });

        Schema::table('letters', function (Blueprint $table): void {
            $table->dropIndex(
                'letters_school_admin_direction_idx'
            );

            $table->dropColumn('administration_type');
        });

        Schema::dropIfExists('scout_administration_profiles');
    }
};