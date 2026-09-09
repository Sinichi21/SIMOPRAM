<?php

use App\Models\SemesterClosure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_verifications', function (Blueprint $table): void {
            $table->unsignedBigInteger('source_id')->nullable()->after('semester_closure_id');
            $table->string('source_type', 180)->nullable()->after('source_id');
            $table->string('document_number', 180)->nullable()->after('document_type');
            $table->string('title')->nullable()->after('document_number');
            $table->json('metadata')->nullable()->after('snapshot_checksum');

            $table->index(['school_id', 'source_type', 'source_id'], 'rv_source_idx');
            $table->index(['school_id', 'document_type', 'issued_at'], 'rv_type_issued_idx');
        });

        Schema::table('report_verifications', function (Blueprint $table): void {
            $table->unsignedBigInteger('semester_closure_id')->nullable()->change();
        });

        DB::table('report_verifications')
            ->whereNotNull('semester_closure_id')
            ->whereNull('source_type')
            ->update([
                'source_type' => SemesterClosure::class,
                'source_id' => DB::raw('semester_closure_id'),
            ]);
    }

    public function down(): void
    {
        DB::table('report_verifications')
            ->where('source_type', SemesterClosure::class)
            ->update([
                'source_type' => null,
                'source_id' => null,
            ]);

        Schema::table('report_verifications', function (Blueprint $table): void {
            $table->dropIndex('rv_source_idx');
            $table->dropIndex('rv_type_issued_idx');
            $table->dropColumn([
                'source_id',
                'source_type',
                'document_number',
                'title',
                'metadata',
            ]);
        });

        DB::table('report_verifications')->whereNull('semester_closure_id')->delete();

        Schema::table('report_verifications', function (Blueprint $table): void {
            $table->unsignedBigInteger('semester_closure_id')->nullable(false)->change();
        });
    }
};
