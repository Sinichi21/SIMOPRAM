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
        | Riwayat Penutupan Semester
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'semester_closures',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'school_id'
                );

                $table->unsignedBigInteger(
                    'academic_year_id'
                );

                $table->unsignedBigInteger(
                    'semester_id'
                );

                $table->unsignedBigInteger(
                    'assessment_config_id'
                );

                /*
                |--------------------------------------------------------------------------
                | Setiap lock baru menghasilkan versi baru.
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedInteger(
                        'version'
                    )
                    ->default(1);

                /*
                |--------------------------------------------------------------------------
                | locked / reopened
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'status',
                        20
                    )
                    ->default('locked');

                /*
                |--------------------------------------------------------------------------
                | Signature config saat dikunci
                |--------------------------------------------------------------------------
                */

                $table
                    ->char(
                        'config_signature',
                        64
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'attendance_source_version'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Integrity Snapshot
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedInteger(
                        'snapshot_count'
                    )
                    ->default(0);

                $table
                    ->char(
                        'snapshot_checksum',
                        64
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Snapshot metadata periode dan konfigurasi
                |--------------------------------------------------------------------------
                */

                $table
                    ->json('metadata')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Lock
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedBigInteger(
                        'locked_by'
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'locked_at'
                    );

                /*
                |--------------------------------------------------------------------------
                | Reopen
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedBigInteger(
                        'reopened_by'
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'reopened_at'
                    )
                    ->nullable();

                $table
                    ->text(
                        'reopen_reason'
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
                        'sc_school_fk'
                    )
                    ->references('id')
                    ->on('schools')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'academic_year_id',
                        'sc_year_fk'
                    )
                    ->references('id')
                    ->on('academic_years')
                    ->restrictOnDelete();

                $table
                    ->foreign(
                        'semester_id',
                        'sc_semester_fk'
                    )
                    ->references('id')
                    ->on('semesters')
                    ->restrictOnDelete();

                $table
                    ->foreign(
                        'assessment_config_id',
                        'sc_config_fk'
                    )
                    ->references('id')
                    ->on('assessment_configs')
                    ->restrictOnDelete();

                $table
                    ->foreign(
                        'locked_by',
                        'sc_locked_by_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'reopened_by',
                        'sc_reopened_by_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Constraints
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'school_id',
                        'academic_year_id',
                        'semester_id',
                        'version',
                    ],
                    'sc_period_version_uq'
                );

                $table->index(
                    [
                        'school_id',
                        'academic_year_id',
                        'semester_id',
                    ],
                    'sc_period_idx'
                );

                $table->index(
                    [
                        'school_id',
                        'status',
                    ],
                    'sc_status_idx'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Snapshot Nilai Semester
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'semester_grade_snapshots',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'school_id'
                );

                $table->unsignedBigInteger(
                    'semester_closure_id'
                );

                /*
                |--------------------------------------------------------------------------
                | ID asli tetap disimpan, tetapi nullable agar histori tidak hilang
                | apabila siswa suatu saat dihapus.
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedBigInteger(
                        'student_id'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Snapshot identitas siswa
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'student_nis',
                        100
                    )
                    ->nullable();

                $table
                    ->string(
                        'student_name',
                        200
                    );

                $table
                    ->unsignedBigInteger(
                        'classroom_id'
                    )
                    ->nullable();

                $table
                    ->string(
                        'classroom_name',
                        150
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Nilai resmi
                |--------------------------------------------------------------------------
                */

                $table
                    ->decimal(
                        'final_score',
                        8,
                        2
                    );

                $table
                    ->string(
                        'letter_grade',
                        20
                    )
                    ->nullable();

                $table
                    ->text(
                        'description'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Breakdown seluruh faktor pada saat semester dikunci
                |--------------------------------------------------------------------------
                */

                $table
                    ->json(
                        'factor_scores'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Metadata sumber nilai
                |--------------------------------------------------------------------------
                */

                $table
                    ->char(
                        'config_signature',
                        64
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'attendance_source_version'
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'source_calculated_at'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Hash setiap snapshot
                |--------------------------------------------------------------------------
                */

                $table
                    ->char(
                        'record_hash',
                        64
                    );

                $table
                    ->timestamp(
                        'created_at'
                    )
                    ->useCurrent();

                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreign(
                        'school_id',
                        'sgs_school_fk'
                    )
                    ->references('id')
                    ->on('schools')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'semester_closure_id',
                        'sgs_closure_fk'
                    )
                    ->references('id')
                    ->on('semester_closures')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'student_id',
                        'sgs_student_fk'
                    )
                    ->references('id')
                    ->on('students')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'classroom_id',
                        'sgs_classroom_fk'
                    )
                    ->references('id')
                    ->on('classrooms')
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Constraints
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'semester_closure_id',
                        'student_id',
                    ],
                    'sgs_closure_student_uq'
                );

                $table->index(
                    [
                        'school_id',
                        'semester_closure_id',
                    ],
                    'sgs_school_closure_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'semester_grade_snapshots'
        );

        Schema::dropIfExists(
            'semester_closures'
        );
    }
};
