<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'announcement_targets',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('announcement_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | all_students
                | all_coaches
                | classroom
                | scout_unit
                |--------------------------------------------------------------------------
                */
                $table->string('target_type', 30);

                /*
                |--------------------------------------------------------------------------
                | classroom/scout_unit membutuhkan ID.
                | all_students/all_coaches = null.
                |--------------------------------------------------------------------------
                */
                $table->unsignedBigInteger('target_id')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'announcement_id',
                        'target_type',
                    ],
                    'announcement_target_type_idx'
                );

                $table->index(
                    [
                        'school_id',
                        'target_type',
                        'target_id',
                    ],
                    'announcement_target_lookup_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'announcement_targets'
        );
    }
};