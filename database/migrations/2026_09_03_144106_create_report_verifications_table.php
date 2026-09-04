<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'report_verifications',
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
                | Kode publik
                |--------------------------------------------------------------------------
                |
                | 24 random bytes = 48 karakter hexadecimal.
                | Tidak memakai ID berurutan sebagai identifier publik.
                |--------------------------------------------------------------------------
                */

                $table->char(
                    'code',
                    48
                );

                $table
                    ->string(
                        'document_type',
                        50
                    )
                    ->default(
                        'grades'
                    );

                /*
                |--------------------------------------------------------------------------
                | Copy checksum snapshot pada saat dokumen diterbitkan.
                |--------------------------------------------------------------------------
                */

                $table
                    ->char(
                        'snapshot_checksum',
                        64
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Issuance
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedBigInteger(
                        'issued_by'
                    )
                    ->nullable();

                $table->timestamp(
                    'issued_at'
                );

                /*
                |--------------------------------------------------------------------------
                | Public verification usage
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedBigInteger(
                        'verification_count'
                    )
                    ->default(0);

                $table
                    ->timestamp(
                        'last_verified_at'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Revocation
                |--------------------------------------------------------------------------
                */

                $table
                    ->timestamp(
                        'revoked_at'
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'revoked_by'
                    )
                    ->nullable();

                $table
                    ->text(
                        'revocation_reason'
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
                        'rv_school_fk'
                    )
                    ->references('id')
                    ->on('schools')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'semester_closure_id',
                        'rv_closure_fk'
                    )
                    ->references('id')
                    ->on('semester_closures')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'issued_by',
                        'rv_issued_by_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'revoked_by',
                        'rv_revoked_by_fk'
                    )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    'code',
                    'rv_code_uq'
                );

                $table->index(
                    [
                        'school_id',
                        'semester_closure_id',
                    ],
                    'rv_school_closure_idx'
                );

                $table->index(
                    [
                        'school_id',
                        'issued_at',
                    ],
                    'rv_school_issued_idx'
                );

                $table->index(
                    [
                        'semester_closure_id',
                        'document_type',
                    ],
                    'rv_closure_type_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'report_verifications'
        );
    }
};
