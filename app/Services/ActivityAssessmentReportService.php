<?php

namespace App\Services;

use App\Models\ActivityAssessment;
use App\Models\ActivityAssessmentReport;
use App\Models\School;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ActivityAssessmentReportService
{
    public function authorize(ActivityAssessment $assessment): void
    {
        abort_unless(auth()->user()?->is_active, 403);
        if ($assessment->school_id === null) {
            app(GlobalActivityAccess::class)->authorize($assessment->activity()->withoutGlobalScope('school')->firstOrFail());
            app(SchoolContext::class)->clear();
        } else {
            abort_unless(app(SchoolContext::class)->id() === $assessment->school_id, 404);
            abort_unless(School::whereKey($assessment->school_id)->where('is_active', true)->exists(), 403);
            abort_unless(auth()->user()->can('activity_assessments.publish') && auth()->user()->can('reports.export'), 403);
        }
    }

    public function issue(ActivityAssessment $assessment, string $format, bool $withSignatures): ActivityAssessmentReport
    {
        $this->authorize($assessment);
        validator(['format' => $format], ['format' => ['required', 'in:complete,judges']])->validate();
        $path = 'assessment-reports/'.Str::random(48).'.pdf';
        try {
            return DB::transaction(function () use ($assessment, $format, $withSignatures, $path): ActivityAssessmentReport {
                $assessment = ActivityAssessment::withoutGlobalScope('school')->lockForUpdate()->findOrFail($assessment->id);
                $this->authorize($assessment);
                if (! $assessment->is_special || ! $assessment->isPublished()) {
                    throw ValidationException::withMessages(['report' => 'Aktifkan form penilaian juri sebelum mencetak rekap.']);
                }
                $rankings = app(PublicAssessmentService::class)->rankings($assessment);
                $judges = app(ActivityJudgeService::class)->resultJudges($assessment);
                if ($judges->isEmpty() || $rankings->isEmpty()) {
                    throw ValidationException::withMessages(['report' => 'Siapkan peserta dan juri sebelum mencetak rekap.']);
                }
                $criteria = $assessment->criteria->sortBy('sort_order')->values()->map(fn ($criterion): array => [
                    'id' => $criterion->id, 'name' => $criterion->name, 'max_score' => $criterion->max_score, 'weight' => $criterion->weight,
                ])->all();
                $detailedRankings = app(ActivityJudgeService::class)->rankings($assessment);
                $judgeForms = $judges->map(function (array $judge) use ($assessment, $detailedRankings): array {
                    $source = $assessment->judges->firstWhere('id', $judge['id']);

                    return $judge + ['rows' => $detailedRankings->map(fn (array $row): array => [
                        'name' => $row['target']->participant_name ?? $row['target']->student?->name ?? $row['target']->scoutUnit?->name ?? 'Peserta',
                        'scores' => $judge['finalized'] ? ($source->scores[$row['target']->id] ?? []) : [],
                        'total' => $row['judge_scores'][$judge['id']] ?? null,
                    ])->all()];
                })->all();
                $school = $assessment->school_id ? School::findOrFail($assessment->school_id)->name : 'Kegiatan Umum SIMPRAM';
                $snapshot = ['activity' => $assessment->activity()->withoutGlobalScope('school')->firstOrFail()->title,
                    'title' => $assessment->title, 'organizer' => $school, 'judges' => $judges->all(), 'criteria' => $criteria,
                    'rankings' => $rankings->all(), 'judge_forms' => $judgeForms,
                    'is_final' => $judges->every(fn (array $judge): bool => $judge['finalized'])];
                $report = ActivityAssessmentReport::create([
                    'school_id' => $assessment->school_id, 'activity_assessment_id' => $assessment->id,
                    'code' => bin2hex(random_bytes(24)), 'format' => $format, 'with_signatures' => $withSignatures,
                    'snapshot' => $snapshot, 'file_path' => $path, 'issued_by' => auth()->id(), 'issued_at' => now(),
                ]);
                $verificationUrl = route('assessment-reports.verify', $report->code);
                $qr = Builder::create()->writer(extension_loaded('gd') ? new PngWriter : new SvgWriter)
                    ->data($verificationUrl)->size(220)->margin(8)->build()->getDataUri();
                $binary = Pdf::loadView('reports.pdf.activity-assessment', compact('report', 'snapshot', 'qr', 'verificationUrl'))
                    ->setPaper(max(count($criteria), $judges->count()) > 8 ? 'a3' : 'a4', 'landscape')
                    ->setOption('isRemoteEnabled', false)->output();
                if (! Storage::disk('local')->put($path, $binary)) {
                    throw ValidationException::withMessages(['report' => 'Arsip cetakan gagal disimpan. Coba kembali.']);
                }
                $report->update(['file_sha256' => hash('sha256', $binary)]);
                app(AssessmentAuditService::class)->record(action: 'activity_assessment.report_issued', subject: $assessment,
                    description: 'Rekap nilai juri diterbitkan dan diarsipkan.', metadata: ['report_id' => $report->id, 'format' => $format], module: 'activity_assessment');

                return $report;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function intact(ActivityAssessmentReport $report): bool
    {
        return $report->file_sha256 && Storage::disk('local')->exists($report->file_path)
            && hash_equals($report->file_sha256, hash('sha256', Storage::disk('local')->get($report->file_path)));
    }
}
