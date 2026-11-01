<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Coach;
use App\Models\Student;
use App\Services\ActivityAssignmentFilter;
use App\Services\GlobalDashboardService;
use App\Support\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function global(Request $request, GlobalDashboardService $dashboard): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        return response()->json(['data' => [
            'mode' => 'global',
            'school' => null,
            'dashboard' => $dashboard->data(),
        ]]);
    }

    public function __invoke(ActivityAssignmentFilter $filter, SchoolContext $context): JsonResponse
    {
        $user = request()->user();
        $mode = $filter->defaultMode($user);
        $activities = Activity::query()->where('approval_status', 'approved');
        $filter->apply($activities, $user, $context->id(), $mode, null);
        $student = Student::query()->where('user_id', $user->id)->first();
        $totalStudents = Student::query()->where('school_id', $context->id())->count();
        $activeStudents = Student::query()->where('school_id', $context->id())->where('status', 'active')->count();
        $totalCoaches = Coach::query()->where('school_id', $context->id())->count();
        $activeCoaches = Coach::query()->where('school_id', $context->id())->where('is_active', true)->count();

        return response()->json(['data' => [
            'school' => ['id' => $context->school()->id, 'name' => $context->school()->name],
            'summary' => [
                'students' => $totalStudents,
                'active_students' => $activeStudents,
                'coaches' => $totalCoaches,
                'active_coaches' => $activeCoaches,
                'activities' => (clone $activities)->count(),
                'upcoming_activities' => (clone $activities)->where('start_at', '>=', now())->count(),
                'attendance_sessions' => AttendanceSession::query()->whereHas('activity', fn ($query) => $filter->apply($query, $user, $context->id(), $mode, null))->count(),
                'my_student_attendance' => $student ? Attendance::query()->where('student_id', $student->id)->count() : null,
            ],
            'upcoming_activities' => ActivityResource::collection((clone $activities)->with(['academicYear', 'coaches'])->where('start_at', '>=', now())->orderBy('start_at')->limit(5)->get()),
        ]]);
    }
}
