<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'school_document_settings',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId('responsible_coach_id')
                    ->nullable()
                    ->constrained('coaches')
                    ->nullOnDelete();

                $table
                    ->string('principal_name', 150)
                    ->nullable();

                $table
                    ->string('principal_nip', 50)
                    ->nullable();

                $table
                    ->string('gudep_male_number', 50)
                    ->nullable();

                $table
                    ->string('gudep_female_number', 50)
                    ->nullable();

                $table
                    ->string('signing_city', 100)
                    ->nullable();

                $table
                    ->text('document_note')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    'school_id',
                    'school_document_settings_school_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'school_document_settings'
        );
    }
};
