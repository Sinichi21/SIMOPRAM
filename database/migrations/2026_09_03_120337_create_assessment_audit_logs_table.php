<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'assessment_audit_logs',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'school_id'
                );

                $table
                    ->unsignedBigInteger(
                        'user_id'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Contoh:
                | activity_score.updated
                | activity_assessment.published
                | attendance_weight.updated
                | assessment.synchronized
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'action',
                    100
                );

                /*
                |--------------------------------------------------------------------------
                | Modul sumber
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'module',
                        100
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Subject/Auditable
                |--------------------------------------------------------------------------
                |
                | Tidak memakai morphs() agar nama index MySQL tetap pendek.
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'subject_type',
                        191
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'subject_id'
                    )
                    ->nullable();

                $table
                    ->string(
                        'description',
                        500
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Snapshot
                |--------------------------------------------------------------------------
                */

                $table
                    ->json(
                        'old_values'
                    )
                    ->nullable();

                $table
                    ->json(
                        'new_values'
                    )
                    ->nullable();

                $table
                    ->json(
                        'metadata'
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'created_at'
                    )
                    ->useCurrent();

                /*
                |--------------------------------------------------------------------------
                | FK - nama pendek agar aman dari batas 64 karakter MySQL
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreign(
                        'school_id',
                        'aal_school_fk'
                    )
                    ->references('id')
                    ->on('schools')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'user_id',
                        'aal_user_fk'
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
                        'created_at',
                    ],
                    'aal_school_created_idx'
                );

                $table->index(
                    [
                        'subject_type',
                        'subject_id',
                    ],
                    'aal_subject_idx'
                );

                $table->index(
                    [
                        'school_id',
                        'action',
                    ],
                    'aal_school_action_idx'
                );

                $table->index(
                    [
                        'school_id',
                        'module',
                    ],
                    'aal_school_module_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'assessment_audit_logs'
        );
    }
};
