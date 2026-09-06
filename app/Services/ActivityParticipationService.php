<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityParticipation;
use App\Models\AssessmentConfig;
use App\Models\Student;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ActivityParticipationService
{
    public function students(Activity $activity): Builder
    {
        $activity->loadMissing('scoutLevels');

        return Student::query()->where('status', 'active')
            ->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $activity->academic_year_id))
            ->when($activity->scoutLevels->isNotEmpty(), fn ($query) => $query->whereHas('scoutLevelHistories',
                fn ($query) => $query->where('academic_year_id', $activity->academic_year_id)
                    ->whereIn('scout_level_id', $activity->scoutLevels->modelKeys())->where('is_active', true)));
    }

    /** @param array<int, array{points: mixed, notes?: string|null}> $entries */
    public function save(Activity $activity, AssessmentConfig $config, int $factorId, float $targetPoints, array $entries): void
    {
        abort_unless(auth()->user()?->can('activity_assessments.score'), 403);
        abort_unless(app(SchoolContext::class)->id() === $activity->school_id && $config->school_id === $activity->school_id, 404);

        DB::transaction(function () use ($activity, $config, $factorId, $targetPoints, $entries): void {
            $config = AssessmentConfig::query()->lockForUpdate()->findOrFail($config->id);
            app(SemesterClosureService::class)->assertOpen($config->academic_year_id, $config->semester_id);
            if (! $config->is_active || $config->academic_year_id !== $activity->academic_year_id
                || ($config->semester_id && $config->semester_id !== $activity->semester_id)) {
                throw ValidationException::withMessages(['configId' => 'Pilih konfigurasi aktif pada periode kegiatan ini.']);
            }
            $config->items()->where('assessment_factor_id', $factorId)
                ->whereHas('factor', fn ($query) => $query->where('source_type', 'manual'))->firstOrFail();
            if ($config->participation_factor_id && (int) $config->participation_factor_id !== $factorId) {
                throw ValidationException::withMessages(['factorId' => 'Faktor keaktifan periode ini sudah ditentukan.']);
            }
            Validator::make(['target' => $targetPoints, 'entries' => $entries], [
                'target' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
                'entries' => ['required', 'array'], 'entries.*' => ['array:points,notes'],
                'entries.*.points' => ['required', 'numeric', 'min:0', 'max:1000000'],
                'entries.*.notes' => ['nullable', 'string', 'max:1000'],
            ])->validate();
            if (collect(array_keys($entries))->diff($this->students($activity)->pluck('id'))->isNotEmpty()) {
                throw ValidationException::withMessages(['entries' => 'Siswa tidak termasuk peserta kegiatan ini.']);
            }
            $config->update(['participation_factor_id' => $factorId, 'participation_target_points' => $targetPoints]);
            foreach ($entries as $studentId => $entry) {
                ActivityParticipation::query()->updateOrCreate([
                    'assessment_config_id' => $config->id, 'activity_id' => $activity->id, 'student_id' => $studentId,
                ], ['points' => $entry['points'], 'notes' => $entry['notes'] ?? null, 'entered_by' => auth()->id()]);
            }
            $this->sync($config);
            app(AssessmentAuditService::class)->record(
                action: 'participation.saved', subject: $activity, description: 'Poin keaktifan kegiatan disimpan.',
                metadata: ['assessment_config_id' => $config->id, 'student_count' => count($entries), 'target_points' => $targetPoints],
                module: 'activity_assessment',
            );
        });
    }

    public function sync(AssessmentConfig $config): int
    {
        if (! $config->participation_factor_id || ! $config->participation_target_points) {
            return 0;
        }
        abort_unless(app(SchoolContext::class)->id() === $config->school_id, 404);
        app(SemesterClosureService::class)->assertOpen($config->academic_year_id, $config->semester_id);
        if (! $config->items()->where('assessment_factor_id', $config->participation_factor_id)->exists()) {
            return 0;
        }
        $totals = ActivityParticipation::query()->where('assessment_config_id', $config->id)
            ->whereHas('activity')->selectRaw('student_id, SUM(points) AS total_points')->groupBy('student_id')->get();
        foreach ($totals as $total) {
            app(StudentScoreWriter::class)->writeAutomatic(
                assessmentConfigId: $config->id, studentId: $total->student_id,
                assessmentFactorId: $config->participation_factor_id,
                score: min(100, round($total->total_points / $config->participation_target_points * 100, 2)),
                source: 'participation', notes: 'Rekap poin keaktifan kegiatan',
            );
        }
        app(AssessmentService::class)->invalidateFinalGrades($config, $totals->pluck('student_id'));

        return $totals->count();
    }
}
