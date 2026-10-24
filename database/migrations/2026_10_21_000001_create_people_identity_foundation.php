<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive foundation for global person identity.
     *
     * Existing identity/profile columns are intentionally retained. This migration
     * does not merge records or populate person_id automatically.
     */
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->char('gender', 1)->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->timestamps();

            $table->index('name', 'people_name_index');
            $table->index(
                ['birth_date', 'gender'],
                'people_birth_date_gender_index'
            );
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('person_id')
                ->nullable()
                ->after('id')
                ->constrained('people')
                ->nullOnDelete();

            $table->index('person_id', 'users_person_id_index');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->foreignId('person_id')
                ->nullable()
                ->after('user_id')
                ->constrained('people')
                ->nullOnDelete();

            $table->index(
                ['school_id', 'person_id'],
                'students_school_person_index'
            );
        });

        Schema::table('coaches', function (Blueprint $table): void {
            $table->foreignId('person_id')
                ->nullable()
                ->after('user_id')
                ->constrained('people')
                ->nullOnDelete();

            $table->index(
                ['school_id', 'person_id'],
                'coaches_school_person_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table): void {
            $table->dropForeign(['person_id']);
            $table->dropIndex('coaches_school_person_index');
            $table->dropColumn('person_id');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropForeign(['person_id']);
            $table->dropIndex('students_school_person_index');
            $table->dropColumn('person_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['person_id']);
            $table->dropIndex('users_person_id_index');
            $table->dropColumn('person_id');
        });

        Schema::dropIfExists('people');
    }
};
