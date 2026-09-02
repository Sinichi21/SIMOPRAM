<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Form Penilaian Kegiatan
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'activity_assessments',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger('school_id');

                $table->unsignedBigInteger('activity_id');

                $table->unsignedBigInteger(
                    'assessment_factor_id'
                );

                $table->string(
                    'title',
                    200
                );

                /*
                |--------------------------------------------------------------------------
                | individual / team
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'mode',
                        20
                    )
                    ->default(
                        'individual'
                    );

                /*
                |--------------------------------------------------------------------------
                | draft / published / archived
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'status',
                        20
                    )
                    ->default(
                        'draft'
                    );

                $table
                    ->text('description')
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'created_by'
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'published_by'
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'published_at'
                    )
                    ->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreign(
                        'school_id',
                        'aa_school_fk'
                    )
                    ->references('id')
                    ->on('schools')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'activity_id',
                        'aa_activity_fk'
                    )
                    ->references('id')
                    ->on('activities')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'assessment_factor_id',
                        'aa_factor_fk'
                    )
                    ->references('id')
                    ->on('assessment_factors')
                    ->restrictOnDelete();

                $table
                    ->foreign(
                        'created_by',
                        'aa_created_by_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'published_by',
                        'aa_published_by_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Index
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'school_id',
                        'status',
                    ],
                    'aa_school_status_idx'
                );

                $table->index(
                    [
                        'activity_id',
                        'assessment_factor_id',
                    ],
                    'aa_activity_factor_idx'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Kriteria Penilaian
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'activity_assessment_criteria',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'school_id'
                );

                $table->unsignedBigInteger(
                    'activity_assessment_id'
                );

                $table->string(
                    'name',
                    150
                );

                $table
                    ->text('description')
                    ->nullable();

                $table
                    ->decimal(
                        'max_score',
                        8,
                        2
                    )
                    ->default(100);

                /*
                |--------------------------------------------------------------------------
                | Total bobot seluruh kriteria = 100%
                |--------------------------------------------------------------------------
                */

                $table
                    ->decimal(
                        'weight',
                        5,
                        2
                    )
                    ->default(0);

                $table
                    ->unsignedSmallInteger(
                        'sort_order'
                    )
                    ->default(0);

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreign(
                        'school_id',
                        'aac_school_fk'
                    )
                    ->references('id')
                    ->on('schools')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'activity_assessment_id',
                        'aac_assessment_fk'
                    )
                    ->references('id')
                    ->on('activity_assessments')
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Index
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'activity_assessment_id',
                        'sort_order',
                    ],
                    'aac_assessment_order_idx'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Target Penilaian
        |--------------------------------------------------------------------------
        |
        | individual:
        |   student_id terisi
        |
        | team:
        |   scout_unit_id terisi
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'activity_assessment_targets',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'school_id'
                );

                $table->unsignedBigInteger(
                    'activity_assessment_id'
                );

                $table
                    ->unsignedBigInteger(
                        'student_id'
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'scout_unit_id'
                    )
                    ->nullable();

                $table
                    ->decimal(
                        'total_score',
                        10,
                        2
                    )
                    ->default(0);

                $table
                    ->decimal(
                        'normalized_score',
                        5,
                        2
                    )
                    ->default(0);

                $table
                    ->text('notes')
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'assessed_by'
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'assessed_at'
                    )
                    ->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreign(
                        'school_id',
                        'aat_school_fk'
                    )
                    ->references('id')
                    ->on('schools')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'activity_assessment_id',
                        'aat_assessment_fk'
                    )
                    ->references('id')
                    ->on('activity_assessments')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'student_id',
                        'aat_student_fk'
                    )
                    ->references('id')
                    ->on('students')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'scout_unit_id',
                        'aat_unit_fk'
                    )
                    ->references('id')
                    ->on('scout_units')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'assessed_by',
                        'aat_assessed_by_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Unique
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'activity_assessment_id',
                        'student_id',
                    ],
                    'aat_student_uq'
                );

                $table->unique(
                    [
                        'activity_assessment_id',
                        'scout_unit_id',
                    ],
                    'aat_unit_uq'
                );


                /*
                |--------------------------------------------------------------------------
                | Index
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'school_id',
                        'activity_assessment_id',
                    ],
                    'aat_school_assessment_idx'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Snapshot Anggota Regu
        |--------------------------------------------------------------------------
        |
        | Snapshot diperlukan agar histori penilaian tidak berubah ketika
        | susunan anggota regu pada master data berubah di kemudian hari.
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'activity_assessment_target_members',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'school_id'
                );

                $table->unsignedBigInteger(
                    'activity_assessment_target_id'
                );

                $table->unsignedBigInteger(
                    'student_id'
                );

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreign(
                        'school_id',
                        'aatm_school_fk'
                    )
                    ->references('id')
                    ->on('schools')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'activity_assessment_target_id',
                        'aatm_target_fk'
                    )
                    ->references('id')
                    ->on('activity_assessment_targets')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'student_id',
                        'aatm_student_fk'
                    )
                    ->references('id')
                    ->on('students')
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Unique
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'activity_assessment_target_id',
                        'student_id',
                    ],
                    'aatm_target_student_uq'
                );


                $table->index(
                    [
                        'school_id',
                        'student_id',
                    ],
                    'aatm_school_student_idx'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Nilai Per Kriteria
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'activity_assessment_scores',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'school_id'
                );

                $table->unsignedBigInteger(
                    'activity_assessment_target_id'
                );

                $table->unsignedBigInteger(
                    'activity_assessment_criterion_id'
                );

                $table
                    ->decimal(
                        'score',
                        8,
                        2
                    )
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Nilai setelah dikalikan bobot.
                |--------------------------------------------------------------------------
                */

                $table
                    ->decimal(
                        'weighted_score',
                        8,
                        4
                    )
                    ->default(0);

                $table
                    ->text('notes')
                    ->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreign(
                        'school_id',
                        'aas_school_fk'
                    )
                    ->references('id')
                    ->on('schools')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'activity_assessment_target_id',
                        'aas_target_fk'
                    )
                    ->references('id')
                    ->on('activity_assessment_targets')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'activity_assessment_criterion_id',
                        'aas_criterion_fk'
                    )
                    ->references('id')
                    ->on('activity_assessment_criteria')
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Unique
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'activity_assessment_target_id',
                        'activity_assessment_criterion_id',
                    ],
                    'aas_target_criterion_uq'
                );


                $table->index(
                    [
                        'school_id',
                        'activity_assessment_target_id',
                    ],
                    'aas_school_target_idx'
                );
            }
        );
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Drop dalam urutan terbalik karena Foreign Key
        |--------------------------------------------------------------------------
        */

        Schema::dropIfExists(
            'activity_assessment_scores'
        );

        Schema::dropIfExists(
            'activity_assessment_target_members'
        );

        Schema::dropIfExists(
            'activity_assessment_targets'
        );

        Schema::dropIfExists(
            'activity_assessment_criteria'
        );

        Schema::dropIfExists(
            'activity_assessments'
        );
    }
};