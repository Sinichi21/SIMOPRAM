<?php

namespace App\Console\Commands;

use App\Models\FinalGrade;
use App\Models\StudentScore;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AssessmentSchemaAudit extends Command
{
    protected $signature = 'simopram:audit-assessment-schema
                            {--json : Tampilkan hasil sebagai JSON}';

    protected $description =
        'Audit read-only schema penilaian SIMOPRAM dan deteksi risiko overwrite nilai manual.';

    protected array $tables = [
        'student_scores',
        'final_grades',
        'activity_assessments',
        'activity_assessment_criteria',
        'activity_assessment_targets',
        'activity_assessment_target_members',
        'activity_assessment_scores',
        'attendance_score_settings',
        'semester_closures',
        'semester_grade_snapshots',
        'assessment_audit_logs',
        'report_verifications',
    ];

    public function handle(): int
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'database_connection' => config('database.default'),
            'tables' => [],
            'models' => [],
            'checks' => [],
        ];

        foreach ($this->tables as $table) {
            $report['tables'][$table] = $this->inspectTable($table);
        }

        $report['models']['StudentScore'] =
            $this->inspectModel(StudentScore::class);

        $report['models']['FinalGrade'] =
            $this->inspectModel(FinalGrade::class);

        $report['checks'] = $this->runChecks($report);

        if ($this->option('json')) {
            $this->line(
                json_encode(
                    $report,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
            );

            return self::SUCCESS;
        }

        $this->renderHumanReport($report);

        return collect($report['checks'])
            ->contains(
                fn (array $check): bool =>
                    ($check['level'] ?? null) === 'critical'
            )
                ? self::FAILURE
                : self::SUCCESS;
    }

    protected function inspectTable(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [
                'exists' => false,
                'columns' => [],
                'indexes' => [],
                'foreign_keys' => [],
            ];
        }

        try {
            $columns = collect(Schema::getColumns($table))
                ->map(
                    fn (array $column): array => [
                        'name' => $column['name'] ?? null,
                        'type' =>
                            $column['type_name']
                            ?? $column['type']
                            ?? null,
                        'nullable' => $column['nullable'] ?? null,
                        'default' => $column['default'] ?? null,
                    ]
                )
                ->values()
                ->all();
        } catch (Throwable $e) {
            $columns = [['error' => $e->getMessage()]];
        }

        try {
            $indexes = collect(Schema::getIndexes($table))
                ->map(
                    fn (array $index): array => [
                        'name' => $index['name'] ?? null,
                        'columns' =>
                            array_values($index['columns'] ?? []),
                        'unique' =>
                            (bool) ($index['unique'] ?? false),
                        'primary' =>
                            (bool) ($index['primary'] ?? false),
                    ]
                )
                ->values()
                ->all();
        } catch (Throwable $e) {
            $indexes = [['error' => $e->getMessage()]];
        }

        try {
            $foreignKeys = collect(Schema::getForeignKeys($table))
                ->map(
                    fn (array $fk): array => [
                        'name' => $fk['name'] ?? null,
                        'columns' =>
                            array_values($fk['columns'] ?? []),
                        'foreign_table' =>
                            $fk['foreign_table'] ?? null,
                        'foreign_columns' =>
                            array_values(
                                $fk['foreign_columns'] ?? []
                            ),
                    ]
                )
                ->values()
                ->all();
        } catch (Throwable $e) {
            $foreignKeys = [['error' => $e->getMessage()]];
        }

        return [
            'exists' => true,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];
    }

    protected function inspectModel(string $modelClass): array
    {
        try {
            /** @var Model $model */
            $model = app($modelClass);

            return [
                'class' => $modelClass,
                'table' => $model->getTable(),
                'fillable' => $model->getFillable(),
                'guarded' => $model->getGuarded(),
                'casts' => $model->getCasts(),
            ];
        } catch (Throwable $e) {
            return [
                'class' => $modelClass,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function runChecks(array $report): array
    {
        $checks = [];

        $studentScores =
            $report['tables']['student_scores']
            ?? [];

        if (! ($studentScores['exists'] ?? false)) {
            return [[
                'id' => 'student_scores.exists',
                'level' => 'critical',
                'message' =>
                    'Tabel student_scores tidak ditemukan.',
            ]];
        }

        $columnNames = collect(
            $studentScores['columns'] ?? []
        )
            ->pluck('name')
            ->filter()
            ->values();

        foreach (
            [
                'assessment_config_id',
                'student_id',
                'assessment_factor_id',
                'score',
            ]
            as $column
        ) {
            $exists = $columnNames->contains($column);

            $checks[] = [
                'id' => 'student_scores.column.'.$column,
                'level' => $exists ? 'ok' : 'critical',
                'message' => $exists
                    ? "Kolom {$column} tersedia."
                    : "Kolom wajib {$column} tidak ditemukan.",
            ];
        }

        foreach (
            [
                'source',
                'source_key',
                'source_version',
                'source_synced_at',
            ]
            as $column
        ) {
            $exists = $columnNames->contains($column);

            $checks[] = [
                'id' => 'student_scores.optional.'.$column,
                'level' => $exists ? 'ok' : 'info',
                'message' => $exists
                    ? "Kolom {$column} tersedia."
                    : "Kolom {$column} belum tersedia.",
            ];
        }

        $identityColumns = [
            'assessment_config_id',
            'student_id',
            'assessment_factor_id',
        ];

        $uniqueIndexes = collect(
            $studentScores['indexes'] ?? []
        )->filter(
            fn (array $index): bool =>
                (bool) ($index['unique'] ?? false)
        );

        $dangerousUnique =
            $uniqueIndexes->first(
                fn (array $index): bool =>
                    $this->sameColumnSet(
                        $index['columns'] ?? [],
                        $identityColumns
                    )
            );

        $sourceAwareUnique =
            $uniqueIndexes->first(
                function (array $index) use (
                    $identityColumns
                ): bool {
                    $columns = $index['columns'] ?? [];

                    $hasIdentity = collect(
                        $identityColumns
                    )->every(
                        fn (string $column): bool =>
                            in_array(
                                $column,
                                $columns,
                                true
                            )
                    );

                    $hasSource =
                        in_array(
                            'source',
                            $columns,
                            true
                        )
                        ||
                        in_array(
                            'source_key',
                            $columns,
                            true
                        );

                    return $hasIdentity && $hasSource;
                }
            );

        if ($dangerousUnique) {
            $checks[] = [
                'id' =>
                    'student_scores.manual_overwrite_risk',
                'level' =>
                    'critical',
                'message' =>
                    'Ditemukan UNIQUE hanya pada '
                    . '(assessment_config_id, student_id, assessment_factor_id) '
                    . 'yaitu index '
                    . ($dangerousUnique['name'] ?? '(tanpa nama)')
                    . '. Nilai otomatis berpotensi menimpa nilai manual.',
            ];
        } elseif ($sourceAwareUnique) {
            $checks[] = [
                'id' =>
                    'student_scores.manual_overwrite_risk',
                'level' =>
                    'ok',
                'message' =>
                    'Unique identity sudah membedakan sumber nilai melalui index '
                    . ($sourceAwareUnique['name'] ?? '(tanpa nama)')
                    . '.',
            ];
        } else {
            $checks[] = [
                'id' =>
                    'student_scores.manual_overwrite_risk',
                'level' =>
                    'warning',
                'message' =>
                    'Tidak ditemukan unique identity yang dapat disimpulkan aman. '
                    . 'Perlu review migration/model StudentScore sebelum mengubah sinkronisasi.',
            ];
        }

        $finalGrades =
            $report['tables']['final_grades']
            ?? [];

        if ($finalGrades['exists'] ?? false) {
            $finalColumnNames = collect(
                $finalGrades['columns'] ?? []
            )
                ->pluck('name')
                ->filter();

            foreach (
                [
                    'attendance_source_version',
                    'assessment_config_signature',
                    'calculated_at',
                    'calculated_by',
                ]
                as $column
            ) {
                $exists =
                    $finalColumnNames
                        ->contains($column);

                $checks[] = [
                    'id' =>
                        'final_grades.metadata.'.$column,
                    'level' =>
                        $exists ? 'ok' : 'warning',
                    'message' =>
                        $exists
                            ? "FinalGrade memiliki {$column}."
                            : "FinalGrade belum memiliki {$column}.",
                ];
            }
        } else {
            $checks[] = [
                'id' =>
                    'final_grades.exists',
                'level' =>
                    'critical',
                'message' =>
                    'Tabel final_grades tidak ditemukan.',
            ];
        }

        return $checks;
    }

    protected function sameColumnSet(
        array $left,
        array $right
    ): bool {
        sort($left);
        sort($right);

        return $left === $right;
    }

    protected function renderHumanReport(
        array $report
    ): void {
        $this->newLine();
        $this->info(
            'SIMOPRAM Assessment Schema Audit'
        );
        $this->line(
            'Generated: '.$report['generated_at']
        );
        $this->line(
            'Database: '.$report['database_connection']
        );
        $this->newLine();

        foreach ($report['checks'] as $check) {
            $message =
                '['
                . strtoupper($check['level'] ?? 'info')
                . '] '
                . ($check['message'] ?? '');

            match ($check['level'] ?? null) {
                'critical' => $this->error($message),
                'warning' => $this->warn($message),
                'ok' => $this->info($message),
                default => $this->line($message),
            };
        }

        $this->newLine();
        $this->info('student_scores indexes');

        $indexes =
            $report['tables']['student_scores']['indexes']
            ?? [];

        if (count($indexes) === 0) {
            $this->line(
                '(tidak ada index / tidak dapat dibaca)'
            );
        } else {
            $this->table(
                ['Name', 'Columns', 'Unique', 'Primary'],
                collect($indexes)
                    ->map(
                        fn (array $index): array => [
                            $index['name'] ?? '-',
                            implode(
                                ', ',
                                $index['columns'] ?? []
                            ),
                            ($index['unique'] ?? false)
                                ? 'yes'
                                : 'no',
                            ($index['primary'] ?? false)
                                ? 'yes'
                                : 'no',
                        ]
                    )
                    ->all()
            );
        }

        $this->newLine();
        $this->comment(
            'Command ini read-only. Tidak ada migration atau data yang diubah.'
        );
        $this->comment(
            'Hasil lengkap: php artisan simopram:audit-assessment-schema --json'
        );
    }
}
