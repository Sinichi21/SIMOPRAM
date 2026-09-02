<?php

namespace App\Livewire\Attendances;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Classroom;
use App\Models\ScoutUnit;
use App\Models\Student;
use App\Services\AttendanceService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Manage extends Component
{
    public int $activityId;

    public ?int $editingSessionId = null;

    public ?int $selectedSessionId = null;

    public string $name = 'Absensi';

    public string $participant_scope = 'all';

    public ?int $participant_scope_id = null;

    public string $open_at = '';

    public string $late_after = '';

    public string $close_at = '';

    public bool $allow_manual = true;

    public bool $allow_self_checkin = false;

    public string $latitude = '';

    public string $longitude = '';

    public int $radius_m = 100;

    public int $max_accuracy_m = 100;

    public bool $is_active = true;

    public string $participantSearch = '';

    public function mount(
        int $activityId
    ): void {
        $activity = Activity::query()
            ->findOrFail($activityId);

        $this->activityId =
            $activity->id;

        $this->open_at =
            $activity->start_at
                ->copy()
                ->subMinutes(15)
                ->format('Y-m-d\TH:i');

        $this->late_after =
            $activity->start_at
                ->copy()
                ->addMinutes(15)
                ->format('Y-m-d\TH:i');

        $this->close_at =
            $activity->end_at
                ->format('Y-m-d\TH:i');
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'participant_scope' => [
                'required',
                Rule::in([
                    'all',
                    'classroom',
                    'scout_unit',
                ]),
            ],

            'participant_scope_id' => [
                'nullable',
                'integer',
            ],

            'open_at' => [
                'required',
                'date',
            ],

            'late_after' => [
                'nullable',
                'date',
                'after_or_equal:open_at',
                'before_or_equal:close_at',
            ],

            'close_at' => [
                'required',
                'date',
                'after:open_at',
            ],

            'allow_manual' => [
                'boolean',
            ],

            'allow_self_checkin' => [
                'boolean',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'radius_m' => [
                'required',
                'integer',
                'min:10',
                'max:5000',
            ],

            'max_accuracy_m' => [
                'required',
                'integer',
                'min:10',
                'max:1000',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }

    public function saveSession(
        AttendanceService $attendanceService
    ): void {
        abort_unless(
            auth()->user()->can(
                'attendance_sessions.manage'
            ),
            403
        );

        $validated =
            $this->validate();

        if (
            $validated['participant_scope']
            !== 'all'
            &&
            ! $validated[
                'participant_scope_id'
            ]
        ) {
            $this->addError(
                'participant_scope_id',
                'Pilih target peserta.'
            );

            return;
        }

        if (
            $validated['allow_self_checkin']
            &&
            (
                $validated['latitude'] === ''
                ||
                $validated['longitude'] === ''
            )
        ) {
            $this->addError(
                'latitude',
                'Koordinat wajib diisi untuk absensi GPS.'
            );

            return;
        }

        $activity = Activity::query()
            ->findOrFail(
                $this->activityId
            );

        $data = [
            'activity_id' =>
                $activity->id,

            'name' =>
                trim($validated['name']),

            'participant_scope' =>
                $validated[
                    'participant_scope'
                ],

            'participant_scope_id' =>
                $validated[
                    'participant_scope'
                ] === 'all'
                    ? null
                    : $validated[
                        'participant_scope_id'
                    ],

            'open_at' =>
                $validated['open_at'],

            'late_after' =>
                $validated['late_after']
                ?: null,

            'close_at' =>
                $validated['close_at'],

            'allow_manual' =>
                $validated['allow_manual'],

            'allow_self_checkin' =>
                $validated[
                    'allow_self_checkin'
                ],

            'latitude' =>
                $validated['latitude']
                !== ''
                    ? $validated['latitude']
                    : null,

            'longitude' =>
                $validated['longitude']
                !== ''
                    ? $validated['longitude']
                    : null,

            'radius_m' =>
                $validated['radius_m'],

            'max_accuracy_m' =>
                $validated[
                    'max_accuracy_m'
                ],

            'is_active' =>
                $validated['is_active'],
        ];

        if ($this->editingSessionId) {
            $session =
                AttendanceSession::query()
                    ->findOrFail(
                        $this->editingSessionId
                    );

            $targetChanged =
                $session->participant_scope
                !==
                $data['participant_scope']
                ||
                (int)
                $session->participant_scope_id
                !==
                (int)
                $data['participant_scope_id'];

            if (
                $targetChanged
                &&
                $session
                    ->attendances()
                    ->exists()
            ) {
                $this->addError(
                    'participant_scope',
                    'Target tidak dapat diubah karena absensi sudah memiliki data.'
                );

                return;
            }

            $session->update($data);

            if ($targetChanged) {
                $attendanceService
                    ->syncParticipants(
                        $session
                    );
            }

            $message =
                'Sesi absensi berhasil diperbarui.';
        } else {
            $data['created_by'] =
                auth()->id();

            $session =
                AttendanceSession::query()
                    ->create($data);

            $attendanceService
                ->syncParticipants(
                    $session
                );

            $message =
                'Sesi absensi berhasil dibuat.';
        }

        $this->selectedSessionId =
            $session->id;

        $this->resetSessionForm();

        session()->flash(
            'success',
            $message
        );
    }

    public function selectSession(
        int $id
    ): void {
        AttendanceSession::query()
            ->where(
                'activity_id',
                $this->activityId
            )
            ->findOrFail($id);

        $this->selectedSessionId =
            $id;
    }

    public function editSession(
        int $id
    ): void {
        $session =
            AttendanceSession::query()
                ->where(
                    'activity_id',
                    $this->activityId
                )
                ->findOrFail($id);

        $this->editingSessionId =
            $session->id;

        $this->name =
            $session->name;

        $this->participant_scope =
            $session->participant_scope;

        $this->participant_scope_id =
            $session->participant_scope_id;

        $this->open_at =
            $session->open_at
                ->format('Y-m-d\TH:i');

        $this->late_after =
            $session->late_after
                ?->format('Y-m-d\TH:i')
            ?? '';

        $this->close_at =
            $session->close_at
                ->format('Y-m-d\TH:i');

        $this->allow_manual =
            $session->allow_manual;

        $this->allow_self_checkin =
            $session
                ->allow_self_checkin;

        $this->latitude =
            (string)
            ($session->latitude ?? '');

        $this->longitude =
            (string)
            ($session->longitude ?? '');

        $this->radius_m =
            $session->radius_m;

        $this->max_accuracy_m =
            $session->max_accuracy_m
            ?? 100;

        $this->is_active =
            $session->is_active;

        $this->resetValidation();
    }

    public function mark(
        int $studentId,
        string $status,
        AttendanceService $service
    ): void {
        abort_unless(
            auth()->user()->can(
                'attendances.manual'
            ),
            403
        );

        $session =
            AttendanceSession::query()
                ->findOrFail(
                    $this->selectedSessionId
                );

        $student =
            Student::query()
                ->findOrFail(
                    $studentId
                );

        $service->markManual(
            $session,
            $student,
            $status,
            auth()->user()
        );

        session()->flash(
            'success',
            'Kehadiran berhasil diperbarui.'
        );
    }

    public function toggleSession(
        int $id
    ): void {
        abort_unless(
            auth()->user()->can(
                'attendance_sessions.manage'
            ),
            403
        );

        $session =
            AttendanceSession::query()
                ->findOrFail($id);

        $session->update([
            'is_active' =>
                ! $session->is_active,
        ]);
    }

    public function cancelEdit(): void
    {
        $this->resetSessionForm();
    }

    protected function resetSessionForm(): void
    {
        $activity = Activity::query()
            ->findOrFail(
                $this->activityId
            );

        $this->editingSessionId = null;

        $this->name = 'Absensi';

        $this->participant_scope =
            'all';

        $this->participant_scope_id =
            null;

        $this->open_at =
            $activity->start_at
                ->copy()
                ->subMinutes(15)
                ->format('Y-m-d\TH:i');

        $this->late_after =
            $activity->start_at
                ->copy()
                ->addMinutes(15)
                ->format('Y-m-d\TH:i');

        $this->close_at =
            $activity->end_at
                ->format('Y-m-d\TH:i');

        $this->allow_manual = true;

        $this->allow_self_checkin =
            false;

        $this->latitude = '';

        $this->longitude = '';

        $this->radius_m = 100;

        $this->max_accuracy_m = 100;

        $this->is_active = true;

        $this->resetValidation();
    }

    public function render()
    {
        $activity =
            Activity::query()
                ->findOrFail(
                    $this->activityId
                );

        $sessions =
            AttendanceSession::query()
                ->withCount(
                    'participants'
                )
                ->where(
                    'activity_id',
                    $activity->id
                )
                ->orderBy('open_at')
                ->get();

        $classrooms =
            Classroom::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();

        $scoutUnits =
            ScoutUnit::query()
                ->where(
                    'academic_year_id',
                    $activity
                        ->academic_year_id
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();

        $selectedSession = null;

        $participants = collect();

        $attendanceByStudent =
            collect();

        if ($this->selectedSessionId) {
            $selectedSession =
                AttendanceSession::query()
                    ->where(
                        'activity_id',
                        $activity->id
                    )
                    ->find(
                        $this
                            ->selectedSessionId
                    );

            if ($selectedSession) {
                $participants =
                    AttendanceSessionParticipant::query()
                        ->with('student')
                        ->where(
                            'attendance_session_id',
                            $selectedSession->id
                        )
                        ->when(
                            $this->participantSearch,
                            function ($query): void {
                                $search =
                                    '%' .
                                    trim(
                                        $this->participantSearch
                                    ) .
                                    '%';

                                $query->whereHas(
                                    'student',
                                    fn ($studentQuery) =>
                                        $studentQuery
                                            ->where(
                                                'name',
                                                'like',
                                                $search
                                            )
                                            ->orWhere(
                                                'nis',
                                                'like',
                                                $search
                                            )
                                );
                            }
                        )
                        ->get();

                $attendanceByStudent =
                    Attendance::query()
                        ->where(
                            'attendance_session_id',
                            $selectedSession->id
                        )
                        ->get()
                        ->keyBy(
                            'student_id'
                        );
            }
        }

        return view(
            'livewire.attendances.manage',
            compact(
                'activity',
                'sessions',
                'classrooms',
                'scoutUnits',
                'selectedSession',
                'participants',
                'attendanceByStudent'
            )
        );
    }
}