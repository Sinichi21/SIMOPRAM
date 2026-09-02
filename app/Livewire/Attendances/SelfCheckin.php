<?php

namespace App\Livewire\Attendances;

use App\Models\AttendanceSession;
use App\Models\Student;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class SelfCheckin extends Component
{
    public ?string $successMessage = null;


    protected function student(): Student
    {
        return Student::query()
            ->where(
                'user_id',
                auth()->id()
            )
            ->where(
                'status',
                'active'
            )
            ->firstOrFail();
    }


    public function checkIn(
        AttendanceService $service,
        int $sessionId,
        float $latitude,
        float $longitude,
        float $accuracy
    ): void {
        abort_unless(
            auth()->user()->can(
                'attendances.self'
            ),
            403
        );


        /*
        |--------------------------------------------------------------------------
        | Validasi data dari browser
        |--------------------------------------------------------------------------
        */

        $validated =
            Validator::make(
                [
                    'latitude' =>
                        $latitude,

                    'longitude' =>
                        $longitude,

                    'accuracy' =>
                        $accuracy,
                ],
                [
                    'latitude' => [
                        'required',
                        'numeric',
                        'between:-90,90',
                    ],

                    'longitude' => [
                        'required',
                        'numeric',
                        'between:-180,180',
                    ],

                    'accuracy' => [
                        'required',
                        'numeric',
                        'min:0',
                        'max:10000',
                    ],
                ]
            )->validate();


        $student =
            $this->student();


        /*
        |--------------------------------------------------------------------------
        | Session harus memang milik siswa
        |--------------------------------------------------------------------------
        */

        $session =
            AttendanceSession::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'allow_self_checkin',
                    true
                )
                ->whereHas(
                    'participants',
                    fn ($query) =>
                        $query->where(
                            'student_id',
                            $student->id
                        )
                )
                ->findOrFail(
                    $sessionId
                );


        $attendance =
            $service->checkInGps(
                $session,
                $student,
                (float) $validated[
                    'latitude'
                ],
                (float) $validated[
                    'longitude'
                ],
                (float) $validated[
                    'accuracy'
                ]
            );


        $this->successMessage =
            $attendance->status === 'late'
                ? 'Absensi berhasil. Status: Terlambat.'
                : 'Absensi berhasil. Status: Hadir.';
    }


    public function render()
    {
        $student =
            $this->student();

        $sessions =
            AttendanceSession::query()
                ->with([
                    'activity',
                ])
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'allow_self_checkin',
                    true
                )
                ->where(
                    'open_at',
                    '<=',
                    now()
                )
                ->where(
                    'close_at',
                    '>=',
                    now()
                )
                ->whereHas(
                    'participants',
                    fn ($query) =>
                        $query->where(
                            'student_id',
                            $student->id
                        )
                )
                ->with([
                    'attendances' =>
                        fn ($query) =>
                            $query->where(
                                'student_id',
                                $student->id
                            ),
                ])
                ->orderBy('open_at')
                ->get();

        return view(
            'livewire.attendances.self-checkin',
            compact(
                'student',
                'sessions'
            )
        );
    }
}