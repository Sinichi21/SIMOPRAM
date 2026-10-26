<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ActivityIndexRequest;
use App\Http\Requests\Api\V1\ActivitySaveRequest;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Coach;
use App\Services\ActivityAssignmentFilter;
use App\Support\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    public function store(ActivitySaveRequest $request, SchoolContext $context): JsonResponse
    {
        $academicYear = AcademicYear::query()->where('is_active', true)->first();

        if (! $academicYear) {
            return response()->json([
                'message' => 'Sekolah belum memiliki tahun ajaran aktif. Aktifkan tahun ajaran melalui SIMPRAM web terlebih dahulu.',
            ], 422);
        }

        $validated = $request->validated();
        $activity = Activity::query()->create([
            'academic_year_id' => $academicYear->id,
            'created_by' => $request->user()->id,
            'title' => trim($validated['title']),
            'activity_type' => $validated['activity_type'],
            'routine_session_no' => $validated['activity_type'] === 'regular'
                ? (int) $validated['routine_session_no']
                : null,
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'location' => filled($validated['location'] ?? null) ? trim($validated['location']) : null,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'status' => 'draft',
            'is_public' => false,
        ]);

        // Keep the creator's activity visible in the coach-scoped mobile list.
        $coach = Coach::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        if ($coach) {
            $activity->coaches()->sync([
                $coach->id => ['school_id' => $context->id(), 'role' => 'coach'],
            ]);
        }

        $activity->load(['academicYear', 'coaches'])->loadCount('attendanceSessions');

        return (new ActivityResource($activity))->response()->setStatusCode(201);
    }

    public function update(ActivitySaveRequest $request, int $activityId): ActivityResource
    {
        $activity = Activity::query()->findOrFail($activityId);
        $validated = $request->validated();

        $activity->update([
            'title' => trim($validated['title']),
            'activity_type' => $validated['activity_type'],
            'routine_session_no' => $validated['activity_type'] === 'regular'
                ? (int) $validated['routine_session_no']
                : null,
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'location' => filled($validated['location'] ?? null) ? trim($validated['location']) : null,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
        ]);

        $activity->load(['academicYear', 'coaches'])->loadCount('attendanceSessions');

        return new ActivityResource($activity);
    }

    public function index(ActivityIndexRequest $request, ActivityAssignmentFilter $filter, SchoolContext $context): AnonymousResourceCollection
    {
        $user = $request->user();
        $mode = $request->validated('assignment', $filter->defaultMode($user));
        $coachId = $request->integer('coach_id') ?: null;
        $query = Activity::query()->where('approval_status', 'approved')
            ->with(['academicYear', 'coaches'])->withCount('attendanceSessions');
        if ($search = $request->validated('search')) {
            $query->where('title', 'like', '%'.$search.'%');
        }
        $filter->apply($query, $user, $context->id(), $mode, $coachId);

        return ActivityResource::collection($query->orderByDesc('start_at')->paginate($request->integer('per_page', 20)));
    }

    public function show(int $activityId, ActivityAssignmentFilter $filter, SchoolContext $context): ActivityResource
    {
        $user = request()->user();
        $mode = $filter->defaultMode($user);
        $query = Activity::query()->where('approval_status', 'approved');
        $filter->apply($query, $user, $context->id(), $mode, null);
        $activity = $query->with(['academicYear', 'coaches'])->withCount('attendanceSessions')->findOrFail($activityId);

        return new ActivityResource($activity);
    }
}
