<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ActivityIndexRequest;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Models\Activity;
use App\Services\ActivityAssignmentFilter;
use App\Support\SchoolContext;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
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
