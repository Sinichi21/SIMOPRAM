<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_signatory_profiles')) {
            Schema::create('document_signatory_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('position', 150)->nullable();
                $table->string('identifier_type', 10)->nullable();
                $table->string('identifier_number', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(
                    ['school_id', 'user_id'],
                    'doc_signatory_school_user_uq'
                );
            });
        }

        /*
         * MySQL membatasi nama identifier (termasuk FK) sampai 64 karakter.
         * Karena nama kolom kita panjang, semua FK diberi nama pendek eksplisit.
         *
         * Migration ini juga recovery-safe: jika percobaan migration sebelumnya
         * sudah sempat membuat sebagian kolom/FK, rerun tidak menduplikasi.
         */
        $this->ensureNullableUserColumn(
            'principal_signatory_user_id',
            'responsible_coach_id'
        );

        $this->ensureNullableUserColumn(
            'coordinator_signatory_user_id',
            'principal_signatory_user_id'
        );

        $this->ensureNullableUserColumn(
            'responsible_signatory_user_id',
            'coordinator_signatory_user_id'
        );

        $this->ensureNullableUserColumn(
            'default_letter_signatory_user_id',
            'responsible_signatory_user_id'
        );

        $this->ensureUserForeignKey(
            'principal_signatory_user_id',
            'sds_principal_signatory_fk'
        );

        $this->ensureUserForeignKey(
            'coordinator_signatory_user_id',
            'sds_coordinator_signatory_fk'
        );

        $this->ensureUserForeignKey(
            'responsible_signatory_user_id',
            'sds_responsible_signatory_fk'
        );

        $this->ensureUserForeignKey(
            'default_letter_signatory_user_id',
            'sds_default_letter_signatory_fk'
        );

        // Backfill user pembina existing ke profil penandatangan.
        if (Schema::hasTable('coaches')) {
            DB::table('coaches')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->get()
                ->each(function (object $coach): void {
                    DB::table('document_signatory_profiles')->updateOrInsert(
                        [
                            'school_id' => $coach->school_id,
                            'user_id' => $coach->user_id,
                        ],
                        [
                            'position' => $coach->position,
                            'identifier_type' => filled($coach->nip) ? 'NTA' : null,
                            'identifier_number' => $coach->nip,
                            'is_active' => (bool) ($coach->is_active ?? true),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                });

            DB::table('school_document_settings')
                ->whereNotNull('responsible_coach_id')
                ->orderBy('id')
                ->get()
                ->each(function (object $setting): void {
                    $coach = DB::table('coaches')
                        ->where('id', $setting->responsible_coach_id)
                        ->first();

                    if ($coach?->user_id) {
                        DB::table('school_document_settings')
                            ->where('id', $setting->id)
                            ->update([
                                'responsible_signatory_user_id' => $coach->user_id,
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach ([
            'principal_signatory_user_id',
            'coordinator_signatory_user_id',
            'responsible_signatory_user_id',
            'default_letter_signatory_user_id',
        ] as $column) {
            $this->dropForeignKeysForColumn($column);
        }

        Schema::table('school_document_settings', function (Blueprint $table): void {
            foreach ([
                'principal_signatory_user_id',
                'coordinator_signatory_user_id',
                'responsible_signatory_user_id',
                'default_letter_signatory_user_id',
            ] as $column) {
                if (Schema::hasColumn('school_document_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('document_signatory_profiles');
    }

    private function ensureNullableUserColumn(
        string $column,
        string $after
    ): void {
        if (Schema::hasColumn('school_document_settings', $column)) {
            return;
        }

        Schema::table('school_document_settings', function (Blueprint $table) use ($column, $after): void {
            $table->unsignedBigInteger($column)
                ->nullable()
                ->after($after);
        });
    }

    private function ensureUserForeignKey(
        string $column,
        string $constraintName
    ): void {
        if ($this->foreignKeyExistsForColumn($column)) {
            return;
        }

        Schema::table('school_document_settings', function (Blueprint $table) use ($column, $constraintName): void {
            $table->foreign($column, $constraintName)
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    private function foreignKeyExistsForColumn(string $column): bool
    {
        return collect(Schema::getForeignKeys('school_document_settings'))
            ->contains(fn (array $foreignKey): bool => in_array($column, $foreignKey['columns'], true));
    }

    private function dropForeignKeysForColumn(string $column): void
    {
        foreach (Schema::getForeignKeys('school_document_settings') as $foreignKey) {
            if (in_array($column, $foreignKey['columns'], true)) {
                Schema::table('school_document_settings', function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['name'] ?? $foreignKey['columns']);
                });
            }
        }
    }
};
