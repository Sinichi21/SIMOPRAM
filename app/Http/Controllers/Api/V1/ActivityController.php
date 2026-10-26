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
use App\Services\ActivityHierarchyService;
use App\Support\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function store(ActivitySaveRequest $request, SchoolContext $context, ActivityHierarchyService $hierarchy): JsonResponse
    {
        $validated = $request->validated();
        $academicYear = AcademicYear::query()->find($validated['academic_year_id'] ?? null)
            ?? AcademicYear::query()->where('is_active', true)->first();

        if (! $academicYear) {
            return response()->json([
                'message' => 'Sekolah belum memiliki tahun ajaran aktif. Aktifkan tahun ajaran melalui SIMPRAM web terlebih dahulu.',
            ], 422);
        }

        $activity = DB::transaction(function () use ($request, $validated, $academicYear, $context, $hierarchy): Activity {
            $activity = Activity::query()->create([
                'academic_year_id' => $academicYear->id,
                'semester_id' => $validated['semester_id'] ?? null,
                'created_by' => $request->user()->id,
                'title' => trim($validated['title']),
                'activity_type' => $validated['activity_type'],
                'routine_session_no' => $validated['activity_type'] === 'regular' ? (int) $validated['routine_session_no'] : null,
                'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
                'location' => filled($validated['location'] ?? null) ? trim($validated['location']) : null,
                'start_at' => $validated['start_at'],
                'end_at' => $validated['end_at'],
                'status' => $validated['status'] ?? 'draft',
                'is_public' => $validated['is_public'] ?? false,
                'published_at' => ($validated['status'] ?? 'draft') === 'published' ? now() : null,
            ]);
            $hierarchy->assignParent($activity, $validated['parent_activity_id'] ?? null);
            $activity->save();
            $this->syncActivityRelations($activity, $validated, $request->user()->id, $context->id());

            return $activity;
        });

        $activity->load(['academicYear', 'semester', 'coaches', 'scoutLevels', 'parentActivity'])->loadCount('attendanceSessions');

        return (new ActivityResource($activity))->response()->setStatusCode(201);
    }

    public function update(ActivitySaveRequest $request, int $activityId, ActivityHierarchyService $hierarchy, SchoolContext $context): ActivityResource
    {
        $activity = Activity::query()->findOrFail($activityId);
        $validated = $request->validated();
        DB::transaction(function () use ($activity, $validated, $request, $hierarchy, $context): void {
            $activity->update([
                'academic_year_id' => $validated['academic_year_id'] ?? $activity->academic_year_id,
                'semester_id' => array_key_exists('semester_id', $validated) ? $validated['semester_id'] : $activity->semester_id,
                'title' => trim($validated['title']),
                'activity_type' => $validated['activity_type'],
                'routine_session_no' => $validated['activity_type'] === 'regular' ? (int) $validated['routine_session_no'] : null,
                'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
                'location' => filled($validated['location'] ?? null) ? trim($validated['location']) : null,
                'start_at' => $validated['start_at'],
                'end_at' => $validated['end_at'],
                'status' => $validated['status'] ?? $activity->status,
                'is_public' => $validated['is_public'] ?? $activity->is_public,
                'published_at' => ($validated['status'] ?? $activity->status) === 'published'
                    ? ($activity->published_at ?? now())
                    : null,
            ]);
            if (array_key_exists('parent_activity_id', $validated)) {
                $hierarchy->assignParent($activity, $validated['parent_activity_id']);
                $activity->save();
            }
            $this->syncActivityRelations($activity, $validated, $request->user()->id, $context->id());
        });
        $activity->load(['academicYear', 'semester', 'coaches', 'scoutLevels', 'parentActivity'])->loadCount('attendanceSessions');

        return new ActivityResource($activity);
    }

    public function index(ActivityIndexRequest $request, ActivityAssignmentFilter $filter, SchoolContext $context): AnonymousResourceCollection
    {
        $user = $request->user();
        $mode = $request->validated('assignment', $filter->defaultMode($user));
        $coachId = $request->integer('coach_id') ?: null;
        $query = Activity::query()->where('approval_status', 'approved')
            ->with(['academicYear', 'semester', 'coaches', 'scoutLevels', 'parentActivity'])->withCount('attendanceSessions');
        if ($search = $request->validated('search')) {
            $query->where('title', 'like', '%'.$search.'%');
        }
        foreach (['status', 'activity_type', 'academic_year_id'] as $filterKey) {
            if ($request->filled($filterKey)) {
                $query->where($filterKey, $request->input($filterKey));
            }
        }
        if ($request->filled('from')) {
            $query->whereDate('start_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('start_at', '<=', $request->date('to'));
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
        $activity = $query->with(['academicYear', 'semester', 'coaches', 'scoutLevels'])
            ->withCount('attendanceSessions')->findOrFail($activityId);

        if ($user->can('journals.view')) {
            $activity->load('journal');
        }

        return new ActivityResource($activity);
    }

    public function cancel(int $activityId): ActivityResource
    {
        $activity = Activity::query()->findOrFail($activityId);
        $activity->update(['status' => 'cancelled']);
        $activity->load(['academicYear', 'semester', 'coaches', 'scoutLevels', 'parentActivity'])->loadCount('attendanceSessions');

        return new ActivityResource($activity);
    }

    /** @param array<string, mixed> $validated */
    private function syncActivityRelations(Activity $activity, array $validated, int $userId, int $schoolId): void
    {
        if (array_key_exists('coach_ids', $validated)) {
            $syncData = [];
            foreach ($validated['coach_ids'] as $coachId) {
                $syncData[$coachId] = ['school_id' => $schoolId, 'role' => 'coach'];
            }
            $activity->coaches()->sync($syncData);
        } elseif ($activity->wasRecentlyCreated) {
            $coach = Coach::query()->where('user_id', $userId)->where('school_id', $schoolId)->where('is_active', true)->first();
            if ($coach) {
                $activity->coaches()->sync([$coach->id => ['school_id' => $schoolId, 'role' => 'coach']]);
            }
        }
        if (array_key_exists('scout_level_ids', $validated)) {
            $activity->scoutLevels()->sync($validated['scout_level_ids']);
        }
    }
}
