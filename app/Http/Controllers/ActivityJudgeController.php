<?php

namespace App\Http\Controllers;

use App\Services\ActivityJudgeService;
use App\Support\SchoolContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityJudgeController extends Controller
{
    public function show(Request $request, string $token, ActivityJudgeService $service): Response
    {
        return $this->handle($request, $token, $service, false);
    }

    public function store(Request $request, string $token, ActivityJudgeService $service): Response
    {
        return $this->handle($request, $token, $service, true);
    }

    private function handle(Request $request, string $token, ActivityJudgeService $service, bool $write): Response
    {
        $context = app(SchoolContext::class);
        $previousSchool = $context->school();
        try {
            $judge = $service->resolve($token);
            if ($write) {
                $validated = $request->validate(['scores' => 'required|array', 'action' => 'required|in:save,finalize']);
                $finalize = $validated['action'] === 'finalize';
                $service->save($judge, $validated['scores'], $finalize);
                if ($finalize) {
                    return response()->view('assessments.judge-complete')->withHeaders($this->headers());
                }

                return redirect()->route('activity-judges.show', ['token' => $token])->with('status', 'Draft nilai tersimpan.')->withHeaders($this->headers());
            }
            $assessment = $judge->assessment->load('criteria', 'targets.student', 'targets.scoutUnit');

            return response()->view('assessments.judge-form', compact('judge', 'assessment', 'token'))->withHeaders($this->headers());
        } finally {
            $previousSchool ? $context->set($previousSchool) : $context->clear();
        }
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow'];
    }
}
