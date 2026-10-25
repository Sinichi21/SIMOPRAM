<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MarkAttendanceRequest;
use App\Http\Requests\Api\V1\SelfCheckInRequest;
use App\Http\Resources\Api\V1\AttendanceParticipantResource;
use App\Http\Resources\Api\V1\AttendanceResource;
use App\Http\Resources\Api\V1\AttendanceSessionResource;
use App\Models\Activity;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Services\ActivityAssignmentFilter;
use App\Services\AttendanceService;
use App\Support\SchoolContext;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceController extends Controller
{
    public function sessions(int $activityId, ActivityAssignmentFilter $filter, SchoolContext $context): AnonymousResourceCollection
    {
        $user = request()->user();
        $query = Activity::query()->where('approval_status', 'approved');
        $filter->apply($query, $user, $context->id(), $filter->defaultMode($user), null);
        $activity = $query->findOrFail($activityId);

        return AttendanceSessionResource::collection($activity->attendanceSessions()->withCount('participants')->orderByDesc('open_at')->paginate(30));
    }

    public function participants(int $sessionId, ActivityAssignmentFilter $filter, SchoolContext $context): AnonymousResourceCollection
    {
        $session = $this->authorizedSession($sessionId, $filter, $context);
        $participants = $session->participants()->with(['student', 'attendance'])->paginate(50);

        return AttendanceParticipantResource::collection($participants);
    }

    public function mark(MarkAttendanceRequest $request, int $sessionId, int $studentId, ActivityAssignmentFilter $filter, SchoolContext $context, AttendanceService $attendance): AttendanceResource
    {
        $session = $this->authorizedSession($sessionId, $filter, $context);
        $student = Student::query()->findOrFail($studentId);
        $result = $attendance->markManual($session, $student, $request->validated('status'), $request->user(), $request->validated('notes'));

        return new AttendanceResource($result);
    }

    public function checkIn(SelfCheckInRequest $request, int $sessionId, ActivityAssignmentFilter $filter, SchoolContext $context, AttendanceService $attendance): AttendanceResource
    {
        $session = $this->authorizedSession($sessionId, $filter, $context);
        $student = Student::query()->where('user_id', $request->user()->id)->where('status', 'active')->firstOrFail();
        $result = $attendance->checkInGps($session, $student, (float) $request->validated('latitude'), (float) $request->validated('longitude'), (float) $request->validated('accuracy'));

        return new AttendanceResource($result);
    }

    private function authorizedSession(int $sessionId, ActivityAssignmentFilter $filter, SchoolContext $context): AttendanceSession
    {
        $session = AttendanceSession::query()->with('activity')->findOrFail($sessionId);
        $query = Activity::query()->where('approval_status', 'approved');
        $filter->apply($query, request()->user(), $context->id(), $filter->defaultMode(request()->user()), null);
        abort_unless($query->whereKey($session->activity_id)->exists(), 404);

        return $session;
    }
}
