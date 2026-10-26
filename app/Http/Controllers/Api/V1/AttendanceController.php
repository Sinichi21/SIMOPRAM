<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MarkAttendanceRequest;
use App\Http\Requests\Api\V1\SelfCheckInRequest;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Http\Resources\Api\V1\AttendanceParticipantResource;
use App\Http\Resources\Api\V1\AttendanceResource;
use App\Http\Resources\Api\V1\AttendanceSessionResource;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\Student;
use App\Services\ActivityAssignmentFilter;
use App\Services\AttendanceService;
use App\Support\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function activities(
        ActivityAssignmentFilter $filter,
        SchoolContext $context
    ): AnonymousResourceCollection {
        $user = request()->user();
        $query = Activity::query()
            ->with(['academicYear', 'semester', 'coaches', 'scoutLevels'])
            ->withCount('attendanceSessions')
            ->where('approval_status', 'approved');

        $filter->apply($query, $user, (int) $context->id(), $filter->defaultMode($user), null);

        return ActivityResource::collection(
            $query->orderByDesc('start_at')->paginate(30)
        );
    }

    public function selfCheckInSessions(Request $request): JsonResponse
    {
        $student = Student::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();

        $sessions = AttendanceSession::query()
            ->with('activity:id,title')
            ->with(['attendances' => fn ($query) => $query->where('student_id', $student->id)])
            ->where('is_active', true)
            ->where('allow_self_checkin', true)
            ->whereHas('activity', fn ($query) => $query->where('approval_status', 'approved'))
            ->whereHas('participants', fn ($query) => $query->where('student_id', $student->id))
            ->where(function ($query) use ($student): void {
                $query->where('close_at', '>=', now())
                    ->orWhereHas('attendances', fn ($attendance) => $attendance->where('student_id', $student->id));
            })
            ->orderBy('open_at')
            ->get()
            ->map(function (AttendanceSession $session): array {
                $attendance = $session->attendances->first();

                return [
                    'id' => $session->id,
                    'activity_id' => $session->activity_id,
                    'activity_title' => $session->activity?->title,
                    'name' => $session->name,
                    'open_at' => $session->open_at?->format('Y-m-d H:i:s'),
                    'late_after' => $session->late_after?->format('Y-m-d H:i:s'),
                    'close_at' => $session->close_at?->format('Y-m-d H:i:s'),
                    'is_active' => $session->is_active,
                    'allow_self_checkin' => $session->allow_self_checkin,
                    'latitude' => $session->latitude,
                    'longitude' => $session->longitude,
                    'radius_m' => $session->radius_m,
                    'max_accuracy_m' => $session->max_accuracy_m,
                    'attendance' => $attendance ? [
                        'id' => $attendance->id,
                        'status' => $attendance->status,
                        'source' => $attendance->source,
                        'checked_in_at' => $attendance->checked_in_at?->format('Y-m-d H:i:s'),
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'data' => $sessions,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function permissions(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'attendance_sessions_view' => $user->can('attendance_sessions.view'),
                'attendance_sessions_manage' => $user->can('attendance_sessions.manage'),
                'attendance_manual' => $user->can('attendance.manual'),
                'attendance_checkin' => $user->can('attendance.checkin') || $user->can('attendances.self'),
            ],
        ]);
    }

    public function sessionOptions(
        int $activityId,
        ActivityAssignmentFilter $filter,
        SchoolContext $context
    ): JsonResponse {
        $activity = $this->authorizedActivity($activityId, $filter, $context);
        $levelIds = $activity->scoutLevels->modelKeys();

        $classrooms = Classroom::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $scoutLevels = ScoutLevel::query()
            ->when($levelIds !== [], fn ($query) => $query->whereKey($levelIds))
            ->orderBy('sort_order')
            ->get(['id', 'name', 'sort_order']);

        $scoutUnits = ScoutUnit::query()
            ->where('academic_year_id', $activity->academic_year_id)
            ->where('is_active', true)
            ->when($levelIds !== [], fn ($query) => $query->whereIn('scout_level_id', $levelIds))
            ->orderBy('name')
            ->get(['id', 'name', 'scout_level_id', 'academic_year_id']);

        return response()->json([
            'data' => [
                'classrooms' => $classrooms,
                'scout_levels' => $scoutLevels,
                'scout_units' => $scoutUnits,
            ],
        ]);
    }

    public function sessions(
        int $activityId,
        ActivityAssignmentFilter $filter,
        SchoolContext $context
    ): AnonymousResourceCollection {
        $activity = $this->authorizedActivity($activityId, $filter, $context);

        return AttendanceSessionResource::collection(
            $activity->attendanceSessions()
                ->withCount('participants')
                ->orderBy('open_at')
                ->paginate(30)
        );
    }

    public function storeSession(
        Request $request,
        int $activityId,
        ActivityAssignmentFilter $filter,
        SchoolContext $context,
        AttendanceService $attendanceService
    ): JsonResponse {
        $activity = $this->authorizedActivity($activityId, $filter, $context);
        $data = $this->validatedSessionData($request, $activity);

        if ($data['is_active'] && $this->hasOverlappingActiveSession($activity, $data)) {
            throw ValidationException::withMessages([
                'open_at' => 'Waktu sesi bertabrakan dengan sesi aktif lain pada kegiatan ini.',
            ]);
        }

        $session = DB::transaction(function () use ($activity, $data, $request, $attendanceService): AttendanceSession {
            $session = AttendanceSession::query()->create([
                ...$data,
                'activity_id' => $activity->id,
                'created_by' => $request->user()->id,
            ]);
            $attendanceService->syncParticipants($session);

            return $session;
        });

        return (new AttendanceSessionResource($session->loadCount('participants')))
            ->response()
            ->setStatusCode(201);
    }

    public function updateSession(
        Request $request,
        int $sessionId,
        ActivityAssignmentFilter $filter,
        SchoolContext $context,
        AttendanceService $attendanceService
    ): AttendanceSessionResource {
        $session = $this->authorizedSession($sessionId, $filter, $context);
        $activity = $this->authorizedActivity($session->activity_id, $filter, $context);
        $data = $this->validatedSessionData($request, $activity, $session);

        $targetChanged = $session->participant_scope !== $data['participant_scope']
            || (int) $session->participant_scope_id !== (int) ($data['participant_scope_id'] ?? 0);

        if ($targetChanged && $session->attendances()->exists()) {
            throw ValidationException::withMessages([
                'participant_scope' => 'Target peserta tidak dapat diubah karena sesi sudah memiliki data absensi.',
            ]);
        }

        if ($data['is_active'] && $this->hasOverlappingActiveSession($activity, $data, $session)) {
            throw ValidationException::withMessages([
                'open_at' => 'Waktu sesi bertabrakan dengan sesi aktif lain pada kegiatan ini.',
            ]);
        }

        DB::transaction(function () use ($session, $data, $targetChanged, $attendanceService): void {
            $session->update($data);
            if ($targetChanged || $session->attendances()->exists() === false) {
                $attendanceService->syncParticipants($session);
            }
        });

        return new AttendanceSessionResource($session->refresh()->loadCount('participants'));
    }

    public function toggleSession(
        int $sessionId,
        ActivityAssignmentFilter $filter,
        SchoolContext $context,
        AttendanceService $attendanceService
    ): AttendanceSessionResource {
        $session = $this->authorizedSession($sessionId, $filter, $context);
        $activity = $this->authorizedActivity($session->activity_id, $filter, $context);
        $nextActive = $session->is_active === false;

        if ($nextActive && $this->hasOverlappingActiveSession($activity, [
            'open_at' => $session->open_at,
            'close_at' => $session->close_at,
        ], $session)) {
            throw ValidationException::withMessages([
                'is_active' => 'Sesi tidak dapat diaktifkan karena waktunya bertabrakan dengan sesi aktif lain.',
            ]);
        }

        DB::transaction(function () use ($session, $nextActive, $attendanceService): void {
            if ($nextActive && $session->attendances()->exists() === false) {
                $attendanceService->syncParticipants($session);
            }
            $session->update(['is_active' => $nextActive]);
        });

        return new AttendanceSessionResource($session->refresh()->loadCount('participants'));
    }

    public function participants(
        int $sessionId,
        ActivityAssignmentFilter $filter,
        SchoolContext $context
    ): AnonymousResourceCollection {
        $session = $this->authorizedSession($sessionId, $filter, $context);
        $participants = $session->participants()
            ->with(['student.enrollments.classroom'])
            ->orderBy('student_id')
            ->paginate(50);

        // Avoid the instance-bound attendance relationship during eager loading:
        // it can constrain attendance_session_id to null and hide previously saved statuses.
        $studentIds = $participants->getCollection()->pluck('student_id')->all();
        $attendanceByStudent = Attendance::query()
            ->where('attendance_session_id', $session->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $participants->getCollection()->each(function ($participant) use ($attendanceByStudent): void {
            $participant->setRelation('attendance', $attendanceByStudent->get($participant->student_id));
        });

        return AttendanceParticipantResource::collection($participants);
    }

    public function mark(
        MarkAttendanceRequest $request,
        int $sessionId,
        int $studentId,
        ActivityAssignmentFilter $filter,
        SchoolContext $context,
        AttendanceService $attendance
    ): AttendanceResource {
        $session = $this->authorizedSession($sessionId, $filter, $context);
        $student = Student::query()->findOrFail($studentId);
        $result = $attendance->markManual(
            $session,
            $student,
            $request->validated('status'),
            $request->user(),
            $request->validated('notes')
        );

        return new AttendanceResource($result);
    }

    public function checkIn(
        SelfCheckInRequest $request,
        int $sessionId,
        ActivityAssignmentFilter $filter,
        SchoolContext $context,
        AttendanceService $attendance
    ): AttendanceResource {
        $session = $this->authorizedSession($sessionId, $filter, $context);
        $student = Student::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();

        $result = $attendance->checkInGps(
            $session,
            $student,
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            (float) $request->validated('accuracy')
        );

        return new AttendanceResource($result);
    }

    private function authorizedActivity(
        int $activityId,
        ActivityAssignmentFilter $filter,
        SchoolContext $context
    ): Activity {
        $user = request()->user();
        $query = Activity::query()
            ->with('scoutLevels')
            ->where('approval_status', 'approved');
        $filter->apply($query, $user, (int) $context->id(), $filter->defaultMode($user), null);

        return $query->findOrFail($activityId);
    }

    private function authorizedSession(
        int $sessionId,
        ActivityAssignmentFilter $filter,
        SchoolContext $context
    ): AttendanceSession {
        $session = AttendanceSession::query()->with('activity')->findOrFail($sessionId);
        $this->authorizedActivity($session->activity_id, $filter, $context);

        return $session;
    }

    /**
     * Validate session fields and enforce the same participant-target rules as the web form.
     *
     * @return array<string, mixed>
     */
    private function validatedSessionData(
        Request $request,
        Activity $activity,
        ?AttendanceSession $existing = null
    ): array {
        $payload = array_merge([
            'name' => $existing?->name ?? 'Absensi',
            'participant_scope' => $existing?->participant_scope ?? 'all',
            'participant_scope_id' => $existing?->participant_scope_id,
            'open_at' => $existing?->open_at?->format('Y-m-d H:i:s'),
            'late_after' => $existing?->late_after?->format('Y-m-d H:i:s'),
            'close_at' => $existing?->close_at?->format('Y-m-d H:i:s'),
            'allow_manual' => $existing?->allow_manual ?? true,
            'allow_self_checkin' => $existing?->allow_self_checkin ?? false,
            'latitude' => $existing?->latitude,
            'longitude' => $existing?->longitude,
            'radius_m' => $existing?->radius_m ?? 100,
            'max_accuracy_m' => $existing?->max_accuracy_m ?? 100,
            'is_active' => $existing?->is_active ?? true,
        ], $request->all());

        $data = Validator::make($payload, [
            'name' => ['required', 'string', 'max:100'],
            'participant_scope' => ['required', 'in:all,classroom,scout_unit,scout_level'],
            'participant_scope_id' => ['nullable', 'integer'],
            'open_at' => ['required', 'date'],
            'late_after' => ['nullable', 'date', 'after_or_equal:open_at', 'before_or_equal:close_at'],
            'close_at' => ['required', 'date', 'after:open_at'],
            'allow_manual' => ['required', 'boolean'],
            'allow_self_checkin' => ['required', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_m' => ['required', 'integer', 'min:10', 'max:5000'],
            'max_accuracy_m' => ['required', 'integer', 'min:10', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ])->validate();

        $scope = $data['participant_scope'];
        $scopeId = $data['participant_scope_id'] ?? null;

        if ($scope === 'all') {
            $data['participant_scope_id'] = null;
        } else {
            if ($scopeId === null || $scopeId === 0) {
                throw ValidationException::withMessages([
                    'participant_scope_id' => 'Pilih target peserta.',
                ]);
            }

            $levelIds = $activity->scoutLevels->modelKeys();
            $targetExists = match ($scope) {
                'classroom' => Classroom::query()->where('is_active', true)->whereKey($scopeId)->exists(),
                'scout_unit' => ScoutUnit::query()
                    ->where('academic_year_id', $activity->academic_year_id)
                    ->where('is_active', true)
                    ->when($levelIds !== [], fn ($query) => $query->whereIn('scout_level_id', $levelIds))
                    ->whereKey($scopeId)
                    ->exists(),
                'scout_level' => ScoutLevel::query()
                    ->when($levelIds !== [], fn ($query) => $query->whereKey($levelIds))
                    ->whereKey($scopeId)
                    ->exists(),
                default => false,
            };

            if ($targetExists === false) {
                throw ValidationException::withMessages([
                    'participant_scope_id' => 'Target peserta tidak aktif, tidak tersedia di sekolah ini, atau tidak sesuai kegiatan.',
                ]);
            }
            $data['participant_scope_id'] = (int) $scopeId;
        }

        if ($data['allow_self_checkin'] && ($data['latitude'] === null || $data['longitude'] === null || $data['latitude'] === '' || $data['longitude'] === '')) {
            throw ValidationException::withMessages([
                'latitude' => 'Koordinat wajib diisi untuk absensi GPS.',
            ]);
        }

        $data['name'] = trim($data['name']);
        $data['open_at'] = Carbon::parse($data['open_at']);
        $data['late_after'] = $data['late_after'] ? Carbon::parse($data['late_after']) : null;
        $data['close_at'] = Carbon::parse($data['close_at']);
        $data['allow_manual'] = (bool) $data['allow_manual'];
        $data['allow_self_checkin'] = (bool) $data['allow_self_checkin'];
        $data['is_active'] = (bool) $data['is_active'];
        $data['latitude'] = $data['latitude'] !== null && $data['latitude'] !== '' ? (float) $data['latitude'] : null;
        $data['longitude'] = $data['longitude'] !== null && $data['longitude'] !== '' ? (float) $data['longitude'] : null;
        $data['radius_m'] = (int) $data['radius_m'];
        $data['max_accuracy_m'] = (int) $data['max_accuracy_m'];

        return $data;
    }

    private function hasOverlappingActiveSession(
        Activity $activity,
        array $data,
        ?AttendanceSession $ignore = null
    ): bool {
        return AttendanceSession::query()
            ->active()
            ->where('activity_id', $activity->id)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where('open_at', '<', Carbon::parse($data['close_at']))
            ->where('close_at', '>', Carbon::parse($data['open_at']))
            ->exists();
    }
}
