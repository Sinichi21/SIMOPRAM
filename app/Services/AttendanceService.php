<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceHistory;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Classroom;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        protected GeolocationService $geolocation
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Snapshot Peserta
    |--------------------------------------------------------------------------
    */

    public function syncParticipants(
        AttendanceSession $session
    ): void {
        if ($session->attendances()->exists()) {
            throw ValidationException::withMessages([
                'participant_scope' => 'Peserta tidak dapat diubah karena absensi sudah berjalan.',
            ]);
        }

        $activity = $session->activity()->with('scoutLevels')
            ->firstOrFail();

        $levelIds = $activity->scoutLevels->modelKeys();

        if ($session->participant_scope === 'scout_level' && $levelIds !== []
            && ! in_array((int) $session->participant_scope_id, $levelIds, true)) {
            throw ValidationException::withMessages([
                'participant_scope_id' => 'Golongan peserta harus sesuai dengan golongan kegiatan.',
            ]);
        }

        $students = match (
            $session->participant_scope
        ) {
            'all' => Student::query()
                ->where('status', 'active'),

            'classroom' => $this->studentsFromClassroom(
                $session,
                $activity->academic_year_id
            ),

            'scout_unit' => $this->studentsFromScoutUnit(
                $session,
                $activity->academic_year_id
            ),

            'scout_level' => $this->studentsFromScoutLevel($session, $activity->academic_year_id),

            default => throw ValidationException::withMessages([
                'participant_scope' => 'Target peserta tidak valid.',
            ]),
        };

        $studentIds = $students
            ->when($levelIds !== [], fn (Builder $query) => $query->whereHas(
                'scoutLevelHistories',
                fn (Builder $history) => $history
                    ->where('academic_year_id', $activity->academic_year_id)
                    ->where('is_active', true)
                    ->whereIn('scout_level_id', $levelIds)
            ))
            ->pluck('id');

        DB::transaction(
            function () use (
                $session,
                $studentIds
            ): void {
                $session->participants()
                    ->delete();

                foreach ($studentIds as $studentId) {
                    AttendanceSessionParticipant::query()
                        ->create([
                            'attendance_session_id' => $session->id,

                            'student_id' => $studentId,
                        ]);
                }
            }
        );
    }

    /** @return Builder<Student> */
    protected function studentsFromClassroom(
        AttendanceSession $session,
        int $academicYearId
    ): Builder {
        if (! $session->participant_scope_id) {
            throw ValidationException::withMessages([
                'participant_scope_id' => 'Pilih kelas.',
            ]);
        }

        Classroom::query()
            ->findOrFail(
                $session->participant_scope_id
            );

        return Student::query()
            ->where('status', 'active')
            ->whereHas(
                'enrollments',
                fn ($query) => $query
                    ->where(
                        'academic_year_id',
                        $academicYearId
                    )
                    ->where(
                        'classroom_id',
                        $session
                            ->participant_scope_id
                    )
                    ->where(
                        'status',
                        'active'
                    )
            );
    }

    /** @return Builder<Student> */
    protected function studentsFromScoutUnit(
        AttendanceSession $session,
        int $academicYearId
    ): Builder {
        if (! $session->participant_scope_id) {
            throw ValidationException::withMessages([
                'participant_scope_id' => 'Pilih Regu / Barung.',
            ]);
        }

        $unit = ScoutUnit::query()
            ->where(
                'academic_year_id',
                $academicYearId
            )
            ->findOrFail(
                $session->participant_scope_id
            );

        return Student::query()
            ->where('status', 'active')
            ->whereHas(
                'scoutUnitMembers',
                fn ($query) => $query
                    ->where(
                        'scout_unit_id',
                        $unit->id
                    )
                    ->whereNull('left_at')
            );
    }

    /** @return Builder<Student> */
    protected function studentsFromScoutLevel(AttendanceSession $session, int $academicYearId): Builder
    {
        if (! $session->participant_scope_id) {
            throw ValidationException::withMessages(['participant_scope_id' => 'Pilih golongan Pramuka.']);
        }

        ScoutLevel::query()->findOrFail($session->participant_scope_id);

        return Student::query()->where('status', 'active')->whereHas(
            'scoutLevelHistories',
            fn (Builder $query) => $query
                ->where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->where('scout_level_id', $session->participant_scope_id)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Absensi Manual
    |--------------------------------------------------------------------------
    */

    public function markManual(
        AttendanceSession $session,
        Student $student,
        string $status,
        User $user,
        ?string $notes = null,
    ): Attendance {
        if (! $session->is_active) {
            throw ValidationException::withMessages([
                'attendance' => 'Sesi absensi tidak aktif.',
            ]);
        }

        if (! $session->allow_manual) {
            throw ValidationException::withMessages([
                'attendance' => 'Absensi manual tidak diaktifkan.',
            ]);
        }

        $this->ensureParticipant(
            $session,
            $student
        );

        if (
            ! in_array(
                $status,
                [
                    'present',
                    'late',
                    'sick',
                    'excused',
                    'absent',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'attendance' => 'Status kehadiran tidak valid.',
            ]);
        }

        return DB::transaction(
            function () use (
                $session,
                $student,
                $status,
                $user,
                $notes
            ) {
                $attendance =
                    Attendance::query()
                        ->firstOrNew([
                            'attendance_session_id' => $session->id,

                            'student_id' => $student->id,
                        ]);

                $oldStatus =
                    $attendance->exists
                        ? $attendance->status
                        : null;

                $attendance->activity_id =
                    $session->activity_id;

                $attendance->status =
                    $status;

                $attendance->source =
                    'manual';

                $attendance->verified_by =
                    $user->id;

                $attendance->notes =
                    $notes;

                $attendance->checked_in_at =
                    in_array(
                        $status,
                        ['present', 'late'],
                        true
                    )
                        ? now()
                        : null;

                $attendance->save();

                if ($oldStatus !== $status) {
                    AttendanceHistory::query()
                        ->create([
                            'attendance_id' => $attendance->id,

                            'changed_by' => $user->id,

                            'old_status' => $oldStatus,

                            'new_status' => $status,

                            'source' => 'manual',

                            'notes' => $notes,
                        ]);
                }

                return $attendance;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GPS - Dipakai tahap berikutnya
    |--------------------------------------------------------------------------
    */

    public function checkInGps(
        AttendanceSession $session,
        Student $student,
        float $latitude,
        float $longitude,
        float $accuracy
    ): Attendance {
        if (! $session->allow_self_checkin) {
            throw ValidationException::withMessages([
                'location' => 'Absensi mandiri tidak diaktifkan.',
            ]);
        }

        if (! $session->is_active) {
            throw ValidationException::withMessages([
                'location' => 'Sesi absensi tidak aktif.',
            ]);
        }

        $now = now();

        if (
            $now->lt($session->open_at)
            ||
            $now->gt($session->close_at)
        ) {
            throw ValidationException::withMessages([
                'location' => 'Sesi absensi belum dibuka atau sudah ditutup.',
            ]);
        }

        $this->ensureParticipant(
            $session,
            $student
        );

        if (
            $session->max_accuracy_m
            &&
            $accuracy >
            $session->max_accuracy_m
        ) {
            throw ValidationException::withMessages([
                'location' => 'Akurasi lokasi terlalu rendah. Coba aktifkan GPS dengan akurasi tinggi.',
            ]);
        }

        if (
            $session->latitude === null
            ||
            $session->longitude === null
        ) {
            throw ValidationException::withMessages([
                'location' => 'Lokasi sesi absensi belum dikonfigurasi.',
            ]);
        }

        $distance =
            $this->geolocation
                ->distanceMeters(
                    (float) $session->latitude,
                    (float) $session->longitude,
                    $latitude,
                    $longitude
                );

        if ($distance > $session->radius_m) {
            throw ValidationException::withMessages([
                'location' => 'Anda berada di luar radius absensi.',
            ]);
        }

        $existing =
            Attendance::query()
                ->where(
                    'attendance_session_id',
                    $session->id
                )
                ->where(
                    'student_id',
                    $student->id
                )
                ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'location' => 'Kehadiran Anda sudah tercatat. Hubungi pembina jika perlu koreksi.',
            ]);
        }

        $status =
            $session->late_after
            &&
            $now->gt($session->late_after)
                ? 'late'
                : 'present';

        return DB::transaction(
            function () use (
                $existing,
                $session,
                $student,
                $latitude,
                $longitude,
                $accuracy,
                $distance,
                $status
            ) {
                $attendance =
                    $existing
                    ??
                    new Attendance;

                $oldStatus =
                    $attendance->exists
                        ? $attendance->status
                        : null;

                $attendance->attendance_session_id =
                    $session->id;

                $attendance->activity_id =
                    $session->activity_id;

                $attendance->student_id =
                    $student->id;

                $attendance->status =
                    $status;

                $attendance->source =
                    'gps';

                $attendance->checked_in_at =
                    now();

                $attendance->latitude =
                    $latitude;

                $attendance->longitude =
                    $longitude;

                $attendance->accuracy_m =
                    $accuracy;

                $attendance->distance_m =
                    round($distance, 2);

                $attendance->verified_by =
                    null;

                $attendance->save();

                AttendanceHistory::query()
                    ->create([
                        'attendance_id' => $attendance->id,

                        'old_status' => $oldStatus,

                        'new_status' => $status,

                        'source' => 'gps',
                    ]);

                return $attendance;
            }
        );
    }

    protected function ensureParticipant(
        AttendanceSession $session,
        Student $student
    ): void {
        $exists =
            AttendanceSessionParticipant::query()
                ->where(
                    'attendance_session_id',
                    $session->id
                )
                ->where(
                    'student_id',
                    $student->id
                )
                ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'attendance' => 'Siswa bukan peserta sesi absensi ini.',
            ]);
        }
    }
}
