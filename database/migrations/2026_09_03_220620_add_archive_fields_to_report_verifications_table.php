<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'report_verifications',
            function (Blueprint $table): void {
                if (
                    ! Schema::hasColumn(
                        'report_verifications',
                        'file_disk'
                    )
                ) {
                    $table
                        ->string(
                            'file_disk',
                            32
                        )
                        ->default(
                            'local'
                        )
                        ->after(
                            'snapshot_checksum'
                        );
                }

                if (
                    ! Schema::hasColumn(
                        'report_verifications',
                        'file_path'
                    )
                ) {
                    $table
                        ->string(
                            'file_path',
                            500
                        )
                        ->nullable()
                        ->after(
                            'file_disk'
                        );
                }

                if (
                    ! Schema::hasColumn(
                        'report_verifications',
                        'file_name'
                    )
                ) {
                    $table
                        ->string(
                            'file_name',
                            255
                        )
                        ->nullable()
                        ->after(
                            'file_path'
                        );
                }

                if (
                    ! Schema::hasColumn(
                        'report_verifications',
                        'file_sha256'
                    )
                ) {
                    $table
                        ->char(
                            'file_sha256',
                            64
                        )
                        ->nullable()
                        ->after(
                            'file_name'
                        );
                }

                if (
                    ! Schema::hasColumn(
                        'report_verifications',
                        'file_size'
                    )
                ) {
                    $table
                        ->unsignedBigInteger(
                            'file_size'
                        )
                        ->nullable()
                        ->after(
                            'file_sha256'
                        );
                }

                if (
                    ! Schema::hasColumn(
                        'report_verifications',
                        'archived_at'
                    )
                ) {
                    $table
                        ->timestamp(
                            'archived_at'
                        )
                        ->nullable()
                        ->after(
                            'file_size'
                        );
                }
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'report_verifications',
            function (Blueprint $table): void {
                $columns = [
                    'file_disk',
                    'file_path',
                    'file_name',
                    'file_sha256',
                    'file_size',
                    'archived_at',
                ];

                foreach (
                    $columns as $column
                ) {
                    if (
                        Schema::hasColumn(
                            'report_verifications',
                            $column
                        )
                    ) {
                        $table->dropColumn(
                            $column
                        );
                    }
                }
            }
        );
    }
};
