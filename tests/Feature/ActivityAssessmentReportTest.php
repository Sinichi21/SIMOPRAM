<?php

use App\Livewire\Assessments\Activities\Judges;
use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\ActivityAssessmentReport;
use App\Models\ActivityDelegate;
use App\Models\School;
use App\Models\User;
use App\Services\ActivityAssessmentReportService;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** @return array<string, mixed> */
function assessmentReportContext(?School $school = null): array
{
    app(SchoolContext::class)->clear();
    if ($school) {
        app(SchoolContext::class)->set($school);
        session(['active_school_id' => $school->id]);
    }
    test()->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $activity = Activity::factory()->publicRegistration()->create(['school_id' => $school?->id]);
    $assessment = ActivityAssessment::factory()->special()->published()->create(['school_id' => $school?->id,
        'activity_id' => $activity->id, 'results_published_at' => now()]);
    $technique = $assessment->criteria()->create(['name' => 'Teknik', 'max_score' => 50, 'weight' => 60, 'sort_order' => 1]);
    $teamwork = $assessment->criteria()->create(['name' => 'Kerja sama', 'max_score' => 100, 'weight' => 40, 'sort_order' => 2]);
    $targets = collect(['Garuda', 'Rajawali', 'Merpati'])->map(fn (string $name) => $assessment->targets()->create(['participant_name' => $name]));
    $judges = collect(['Gemuh', 'Dian', 'Juri Draft', 'Juri Dicabut'])->map(function (string $name, int $index) use ($assessment, $targets, $technique, $teamwork) {
        $scores = $targets->mapWithKeys(function ($target, int $position) use ($index, $technique, $teamwork): array {
            $percent = $index === 0 ? ($position === 2 ? 20 : 100) : ($index === 1 ? ($position === 2 ? 40 : 50) : 99);

            return [$target->id => [$technique->id => $percent / 2, $teamwork->id => $percent]];
        })->all();

        return $assessment->judges()->create(['name' => $name, 'token_hash' => hash('sha256', 'report-judge-'.$assessment->id.'-'.$index),
            'starts_at' => now()->subHour(), 'expires_at' => now()->addHour(), 'scores' => $scores,
            'finalized_at' => $index === 2 ? null : now(), 'revoked_at' => $index === 3 ? now() : null]);
    });

    return compact('activity', 'assessment', 'judges', 'targets', 'technique', 'teamwork');
}

test('public details show numbered judges individual totals averages and tied rankings without draft scores', function (bool $tenant) {
    extract(assessmentReportContext($tenant ? School::factory()->create() : null));
    $url = $tenant ? route('schools.activities.results', [$activity->school, $activity->id, $assessment->id])
        : route('public.activities.results', [$activity->id, $assessment->id]);
    $response = $this->get($url)->assertOk()->assertSee('Juri 1 - Gemuh')->assertSee('Juri 2 - Dian')
        ->assertSee('Juri 3 - Juri Draft')->assertDontSee('Juri Dicabut')->assertDontSee('99.00')->assertSee('Nilai rata-rata');
    $response->assertViewHas('rankings', fn ($rows) => $rows->pluck('score')->all() === [75.0, 75.0, 30.0]
        && $rows->pluck('rank')->all() === [1, 1, 3]
        && $rows[0]['judge_scores'] === [$judges[0]->id => 100.0, $judges[1]->id => 50.0]);
    $assessment->update(['results_published_at' => null]);
    $this->get($url)->assertNotFound();
})->with([false, true]);

test('admins can issue both formal report formats with optional appropriate signature blocks', function (string $format, bool $signatures) {
    Storage::fake('local');
    extract(assessmentReportContext());
    Livewire::test(Judges::class, ['assessmentId' => $assessment->id])->assertSee('Cetak / export nilai')->assertSee('Juri 1 - Gemuh');
    $this->post(route('assessment-reports.store', $assessment), ['format' => $format, 'with_signatures' => $signatures ? '1' : '0'])
        ->assertSessionHasNoErrors()->assertRedirect();
    $report = ActivityAssessmentReport::firstOrFail();
    expect($report->school_id)->toBeNull()->and($report->snapshot['is_final'])->toBeFalse()
        ->and($report->snapshot['rankings'][0]['score'])->toBe(75)
        ->and($report->snapshot['judge_forms'][2]['rows'][0]['scores'])->toBe([]);
    expect(json_encode($report->snapshot))->not->toContain('token_hash')->not->toContain('Juri Dicabut');
    Storage::disk('local')->assertExists($report->file_path);
    $response = $this->get(route('assessment-reports.show', $report->code))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline');
    $this->get(route('assessment-reports.show', [$report->code, 'download' => 1]))->assertDownload('rekap-penilaian-'.$report->id.'.pdf');
    $html = view('reports.pdf.activity-assessment', ['report' => $report, 'snapshot' => $report->snapshot, 'qr' => 'data:image/png;base64,', 'verificationUrl' => route('assessment-reports.verify', $report->code)])->render();
    expect($html)->toContain('QR validasi')->toContain($report->issued_at->format('Ymd'))->toContain('Tanggal terbit:');
    expect(substr_count($html, '<div class="signature-space">'))->toBe($signatures ? 3 : 0);
    expect(substr_count($html, '<section'))->toBe($format === 'judges' ? 3 : 1);
    if ($format === 'judges') {
        $firstForm = explode('</section>', $html)[0];
        expect($firstForm)->toContain('Gemuh')->toContain('Teknik')->not->toContain('Dian');
    }
    auth()->logout();
    $this->get(route('assessment-reports.verify', $report->code))->assertOk()->assertSee('Arsip cetakan valid')
        ->assertSee('Hasil sementara')->assertDontSee('Garuda')->assertDontSee('token_hash');
    $this->get(route('assessment-reports.show', $report->code))->assertRedirect(route('login'));
})->with([['complete', true], ['complete', false], ['judges', true], ['judges', false]]);

test('archived report snapshots stay unchanged and QR validation detects altered file bytes', function () {
    Storage::fake('local');
    extract(assessmentReportContext());
    $judges[2]->update(['revoked_at' => now()]);
    $report = app(ActivityAssessmentReportService::class)->issue($assessment, 'complete', true);
    expect($report->snapshot['is_final'])->toBeTrue();
    $hash = $report->file_sha256;
    $judges[0]->update(['scores' => []]);
    $this->get(route('assessment-reports.show', $report->code))->assertOk();
    expect($report->fresh()->snapshot['rankings'][0]['score'])->toBe(75)->and($report->fresh()->file_sha256)->toBe($hash);
    Storage::disk('local')->put($report->file_path, 'altered archive');
    $this->get(route('assessment-reports.verify', $report->code))->assertOk()->assertSee('Arsip cetakan tidak valid');
    $this->get(route('assessment-reports.show', $report->code))->assertStatus(409);
});

test('school reports require the matching school context and cannot leak across tenants', function () {
    Storage::fake('local');
    $school = School::factory()->create();
    extract(assessmentReportContext($school));
    $this->post(route('assessment-reports.store', $assessment), ['format' => 'complete', 'with_signatures' => 1])->assertSessionHasNoErrors();
    $report = ActivityAssessmentReport::firstOrFail();
    expect($report->school_id)->toBe($school->id);
    $this->withSession(['active_school_id' => School::factory()->create()->id])
        ->get(route('assessment-reports.show', $report->code))->assertNotFound();
    $this->post(route('assessment-reports.store', $assessment), ['format' => 'judges', 'with_signatures' => 1])->assertNotFound();
    $this->assertDatabaseCount('activity_assessment_reports', 1);
});

test('global delegates can export only the specific activity granted to them', function () {
    Storage::fake('local');
    extract(assessmentReportContext());
    $other = ActivityAssessment::factory()->special()->published()->create([
        'school_id' => null, 'activity_id' => Activity::factory()->publicRegistration()->create()->id,
    ]);
    $delegate = User::factory()->create();
    ActivityDelegate::create(['activity_id' => $activity->id, 'user_id' => $delegate->id, 'status' => 'approved', 'requested_by' => auth()->id()]);
    $this->actingAs($delegate)->post(route('assessment-reports.store', $assessment), ['format' => 'complete', 'with_signatures' => 0])->assertSessionHasNoErrors();
    $report = ActivityAssessmentReport::firstOrFail();
    $this->get(route('assessment-reports.show', $report->code))->assertOk();
    $this->post(route('assessment-reports.store', $other), ['format' => 'complete', 'with_signatures' => 0])->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('assessment-reports.show', $report->code))->assertForbidden();
});

test('report input rejects unknown format and draft assessment without archiving', function () {
    Storage::fake('local');
    extract(assessmentReportContext());
    $this->post(route('assessment-reports.store', $assessment), ['format' => 'unknown', 'with_signatures' => 0])->assertSessionHasErrors('format');
    $assessment->update(['status' => 'draft']);
    $this->post(route('assessment-reports.store', $assessment), ['format' => 'complete', 'with_signatures' => 0])->assertSessionHasErrors('report');
    $this->assertDatabaseCount('activity_assessment_reports', 0);
});
