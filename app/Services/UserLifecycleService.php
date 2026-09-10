<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Coach;
use App\Models\DocumentSignatoryProfile;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Models\UserTransfer;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserLifecycleService
{
    public function syncStudentMembership(Student $student): void
    {
        abort_unless(auth()->user()?->can('students.update'), 403);
        abort_unless((int) $student->school_id === app(SchoolContext::class)->id(), 404);
        if (! $student->user_id) {
            return;
        }
        $user = User::query()->lockForUpdate()->findOrFail($student->user_id);
        abort_unless($user->system_role === 'student', 422);
        $this->setMembership($user, (int) $student->school_id, $student->status === 'active', $student->status);
    }

    public function membership(int $userId, bool $active, string $reason = 'inactive'): void
    {
        $schoolId = $this->schoolId();
        DB::transaction(function () use ($userId, $active, $reason, $schoolId): void {
            $user = $this->managedUser($userId, $schoolId);
            abort_unless(in_array($reason, ['inactive', 'retired', 'transferred'], true), 422);
            $this->setMembership($user, $schoolId, $active, $reason);
        });
    }

    public function account(int $userId, bool $active): void
    {
        $schoolId = $this->schoolId();
        DB::transaction(function () use ($userId, $active, $schoolId): void {
            $user = $this->managedUser($userId, $schoolId);
            abort_unless(auth()->user()->isSuperAdmin() || ! $user->schoolMemberships()
                ->where('school_id', '!=', $schoolId)->where('is_active', true)->whereNull('left_at')->exists(), 403,
                'Akun dipakai di sekolah lain. Penonaktifan total dikelola super admin.');
            abort_unless($user->approval_status === 'approved' && ! $user->activation_pending, 422,
                'Selesaikan persetujuan dan aktivasi akun terlebih dahulu.');
            $user->update(['is_active' => $active]);
        });
    }

    public function requestTransfer(int $userId, int $targetSchoolId, string $reason): UserTransfer
    {
        $schoolId = $this->schoolId();
        Validator::make(['reason' => trim($reason)], ['reason' => ['required', 'string', 'min:5', 'max:1000']])->validate();
        abort_if($schoolId === $targetSchoolId, 422, 'Sekolah tujuan harus berbeda.');
        School::query()->where('is_active', true)->findOrFail($targetSchoolId);

        return DB::transaction(function () use ($userId, $targetSchoolId, $schoolId, $reason): UserTransfer {
            $user = $this->managedUser($userId, $schoolId);
            if ($user->system_role === 'student') {
                $student = Student::query()->where('user_id', $user->id)->firstOrFail();

                return $this->requestStudentTransfer($student->id, $targetSchoolId, $reason);
            }
            abort_unless($user->is_active && $user->approval_status === 'approved', 422, 'Akun harus aktif sebelum transfer.');
            if (UserTransfer::query()->where('user_id', $userId)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['transfer' => 'Masih ada pengajuan transfer yang belum diselesaikan.']);
            }

            return UserTransfer::query()->create([
                'user_id' => $userId, 'from_school_id' => $schoolId, 'to_school_id' => $targetSchoolId,
                'role' => $user->system_role, 'reason' => trim($reason), 'requested_by' => auth()->id(),
                'status' => 'pending',
            ]);
        });
    }

    public function requestStudentTransfer(int $studentId, int $targetSchoolId, string $reason): UserTransfer
    {
        $schoolId = $this->schoolId();
        Validator::make(['reason' => trim($reason)], ['reason' => ['required', 'string', 'min:5', 'max:1000']])->validate();
        abort_if($schoolId === $targetSchoolId, 422, 'Sekolah tujuan harus berbeda.');
        School::query()->where('is_active', true)->findOrFail($targetSchoolId);

        return DB::transaction(function () use ($studentId, $targetSchoolId, $reason, $schoolId): UserTransfer {
            $student = Student::query()->where('school_id', $schoolId)->lockForUpdate()->find($studentId);
            abort_unless($student, 404);
            $this->validateStudentTransfer($student);

            return UserTransfer::query()->create([
                'student_id' => $student->id, 'user_id' => $student->user_id,
                'from_school_id' => $schoolId, 'to_school_id' => $targetSchoolId,
                'role' => 'student', 'status' => 'pending', 'reason' => trim($reason), 'requested_by' => auth()->id(),
            ]);
        });
    }

    protected function validateStudentTransfer(Student $student): void
    {
        if ($student->status === 'transferred' || ($student->status === 'graduated' && UserTransfer::query()->where('student_id', $student->id)->where('status', 'accepted')->exists())) {
            throw ValidationException::withMessages(['transfer' => 'Siswa sudah pindah. Ajukan dari sekolah siswa saat ini.']);
        }
        if ($student->user_id) {
            $user = User::query()->lockForUpdate()->findOrFail($student->user_id);
            abort_unless($user->system_role === 'student', 422);
        }
        if (UserTransfer::query()->where('status', 'pending')->where(function ($query) use ($student): void {
            $query->where('student_id', $student->id);
            if ($student->user_id) {
                $query->orWhere('user_id', $student->user_id);
            }
        })->exists()) {
            throw ValidationException::withMessages(['transfer' => 'Masih ada pengajuan transfer yang belum diselesaikan.']);
        }
    }

    public function requestIncomingStudentTransfer(int $sourceSchoolId, string $nisn, int $yearId, int $classroomId, string $reason): UserTransfer
    {
        $schoolId = $this->schoolId();
        abort_if($schoolId === $sourceSchoolId, 422, 'Sekolah asal harus berbeda.');
        Validator::make(['nisn' => trim($nisn), 'reason' => trim($reason)], [
            'nisn' => ['required', 'string', 'max:30'], 'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ])->validate();
        School::query()->where('is_active', true)->findOrFail($sourceSchoolId);
        AcademicYear::query()->where('school_id', $schoolId)->findOrFail($yearId);
        Classroom::query()->where('school_id', $schoolId)->where('is_active', true)->findOrFail($classroomId);

        return DB::transaction(function () use ($sourceSchoolId, $nisn, $yearId, $classroomId, $reason, $schoolId): UserTransfer {
            $student = Student::withoutGlobalScope('school')->where('school_id', $sourceSchoolId)->where('nisn', trim($nisn))->lockForUpdate()->first();
            if (! $student) {
                throw ValidationException::withMessages(['nisn' => 'Siswa tidak ditemukan di sekolah asal. Periksa NISN atau hubungi admin sekolah asal.']);
            }
            $this->validateStudentTransfer($student);

            return UserTransfer::query()->create([
                'student_id' => $student->id, 'user_id' => $student->user_id, 'from_school_id' => $sourceSchoolId, 'to_school_id' => $schoolId,
                'role' => 'student', 'reason' => trim($reason), 'requested_by' => auth()->id(), 'status' => 'pending',
                'initiated_by_destination' => true, 'target_year_id' => $yearId, 'target_classroom_id' => $classroomId,
            ]);
        });
    }

    public function resolveTransfer(int $transferId, string $decision, ?int $yearId = null, ?int $classroomId = null): void
    {
        $schoolId = $this->schoolId();
        abort_unless(in_array($decision, ['accepted', 'rejected', 'cancelled'], true), 422);
        DB::transaction(function () use ($transferId, $decision, $schoolId, $yearId, $classroomId): void {
            $transfer = UserTransfer::query()
                ->where(fn ($query) => $query->where('from_school_id', $schoolId)->orWhere('to_school_id', $schoolId))
                ->lockForUpdate()->find($transferId);
            abort_unless($transfer, 404);
            abort_unless(($decision === 'cancelled' ? $transfer->requestingSchoolId() : $transfer->approvingSchoolId()) === $schoolId, 404);
            abort_unless($transfer->status === 'pending', 409);
            if ($transfer->role === 'student') {
                if ($decision === 'accepted') {
                    $this->acceptStudentTransfer($transfer, $yearId, $classroomId);
                }
                $transfer->update(['status' => $decision, 'resolved_by' => auth()->id(), 'resolved_at' => now()]);

                return;
            }
            $user = User::query()->lockForUpdate()->findOrFail($transfer->user_id);
            abort_if($user->isSystemAdmin() || $user->id === auth()->id(), 403);
            abort_unless($user->system_role === $transfer->role, 409, 'Peran akun berubah. Ajukan transfer baru.');

            $originalSchool = app(SchoolContext::class)->school();
            try {
                if ($decision === 'accepted') {
                    if ($transfer->initiated_by_destination) {
                        $schoolId = (int) $transfer->to_school_id;
                        $destination = School::query()->where('is_active', true)->findOrFail($schoolId);
                        app(SchoolContext::class)->set($destination);
                        $yearId = $transfer->target_year_id;
                        $classroomId = $transfer->target_classroom_id;
                    }
                    abort_unless($user->is_active && $user->approval_status === 'approved', 422);
                    abort_unless(School::query()->where('is_active', true)->whereKey($schoolId)->exists(), 422);
                    if ($user->system_role === 'coach') {
                        $source = Coach::withoutGlobalScope('school')->where('school_id', $transfer->from_school_id)->where('user_id', $user->id)->first();
                        if ($source && ! Coach::query()->where('school_id', $schoolId)->where('user_id', $user->id)->exists()) {
                            $copy = $source->replicate();
                            $copy->school_id = $schoolId;
                            $copy->is_active = true;
                            $copy->save();
                        }
                    }
                    $this->setMembership($user, $transfer->from_school_id, false, 'transferred');
                    $this->setMembership($user, $schoolId, true, '');
                    if ($user->system_role === 'principal') {
                        DocumentSignatoryProfile::query()->firstOrCreate(
                            ['school_id' => $schoolId, 'user_id' => $user->id],
                            ['position' => 'Kepala Sekolah', 'is_active' => true]
                        );
                    }
                }
                $transfer->update(['status' => $decision, 'resolved_by' => auth()->id(), 'resolved_at' => now()]);
            } finally {
                app(SchoolContext::class)->set($originalSchool);
            }
        });
    }

    protected function schoolId(): int
    {
        abort_unless(auth()->user()?->can('user_approvals.manage'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409);

        return $schoolId;
    }

    protected function managedUser(int $userId, int $schoolId): User
    {
        $user = User::query()->whereHas('schoolMemberships', fn ($query) => $query->where('school_id', $schoolId))
            ->lockForUpdate()->find($userId);
        abort_unless($user, 404);
        abort_if($user->isSystemAdmin() || $user->id === auth()->id(), 403);
        abort_if($user->system_role === 'school_admin' && ! auth()->user()->isSuperAdmin(), 403);
        abort_unless(in_array($user->system_role, ['student', 'coach', 'school_admin', 'principal'], true), 422);

        return $user;
    }

    protected function setMembership(User $user, int $schoolId, bool $active, string $reason): void
    {
        $membership = SchoolUserMembership::query()->firstOrNew(['school_id' => $schoolId, 'user_id' => $user->id]);
        $membership->fill([
            'is_active' => $active, 'joined_at' => $membership->joined_at ?? now()->toDateString(),
            'left_at' => $active ? null : now()->toDateString(),
            'exit_reason' => $active ? null : ($membership->exit_reason === 'graduated' ? 'graduated' : $reason),
        ])->save();
        $previousTeam = getPermissionsTeamId();
        try {
            setPermissionsTeamId($schoolId);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $user->syncRoles($active ? [$user->system_role] : []);
        } finally {
            setPermissionsTeamId($previousTeam);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
        Coach::withoutGlobalScope('school')->where('school_id', $schoolId)->where('user_id', $user->id)->update(['is_active' => $active]);
        DocumentSignatoryProfile::withoutGlobalScope('school')->where('school_id', $schoolId)->where('user_id', $user->id)->update(['is_active' => $active]);
        $students = Student::withoutGlobalScope('school')->where('school_id', $schoolId)->where('user_id', $user->id)->get();
        foreach ($students as $student) {
            $wasInactive = $student->status === 'inactive';
            if ($student->status !== 'graduated' || $active) {
                $student->update(['status' => $active ? 'active' : ($reason === 'transferred' ? 'transferred' : 'inactive')]);
            }
            if (! $active) {
                StudentEnrollment::withoutGlobalScope('school')->where('school_id', $schoolId)
                    ->where('student_id', $student->id)->where('status', 'active')
                    ->update(['status' => $student->status, 'completed_at' => now()->toDateString()]);
            } elseif ($wasInactive) {
                StudentEnrollment::withoutGlobalScope('school')->where('school_id', $schoolId)
                    ->where('student_id', $student->id)->where('status', 'inactive')
                    ->whereHas('academicYear', fn ($year) => $year->where('is_active', true))
                    ->update(['status' => 'active', 'completed_at' => null]);
            }
        }
    }

    protected function acceptStudentTransfer(UserTransfer $transfer, ?int $yearId, ?int $classroomId): void
    {
        $sourceQuery = Student::withoutGlobalScope('school')->where('school_id', $transfer->from_school_id)->lockForUpdate();
        $source = $transfer->student_id
            ? $sourceQuery->findOrFail($transfer->student_id)
            : $sourceQuery->where('user_id', $transfer->user_id)->firstOrFail();
        abort_if($source->status === 'transferred', 409, 'Siswa sudah pindah sekolah.');
        abort_if($source->status === 'graduated' && UserTransfer::query()->where('student_id', $source->id)->where('status', 'accepted')->exists(), 409, 'Alumni sudah ditransfer ke sekolah lain.');
        $user = $source->user_id ? User::query()->lockForUpdate()->findOrFail($source->user_id) : null;
        if ($user) {
            abort_unless($user->system_role === 'student', 409, 'Peran akun berubah.');
        }
        $originalSchool = app(SchoolContext::class)->school();
        try {
            $destination = School::query()->where('is_active', true)->findOrFail($transfer->to_school_id);
            app(SchoolContext::class)->set($destination);
            if ($transfer->initiated_by_destination) {
                $yearId = $transfer->target_year_id;
                $classroomId = $transfer->target_classroom_id;
            }
            Validator::make(['yearId' => $yearId, 'classroomId' => $classroomId], [
                'yearId' => ['required', 'integer'], 'classroomId' => ['required', 'integer'],
            ])->validate();
            AcademicYear::query()->findOrFail($yearId);
            Classroom::query()->where('is_active', true)->findOrFail($classroomId);
            $target = $this->copyStudent($source, $destination->id, $yearId, $classroomId, $transfer);
            if ($source->status !== 'graduated') {
                $source->update(['status' => 'transferred']);
            }
            StudentEnrollment::withoutGlobalScope('school')->where('school_id', $source->school_id)
                ->where('student_id', $source->id)->where('status', 'active')
                ->update(['status' => $source->status, 'completed_at' => now()->toDateString()]);
            $target->update(['status' => 'active']);
            if ($user) {
                $this->setMembership($user, $source->school_id, false, 'transferred');
                $this->setMembership($user, $destination->id, true, '');
            }
            $transfer->fill(['student_id' => $source->id, 'target_student_id' => $target->id, 'user_id' => $source->user_id]);
        } finally {
            app(SchoolContext::class)->set($originalSchool);
        }
    }

    protected function copyStudent(Student $source, int $targetSchoolId, int $yearId, int $classroomId, UserTransfer $transfer): Student
    {
        $relatedIds = [$source->id];
        $frontier = $relatedIds;
        while ($frontier !== []) {
            $links = UserTransfer::query()->where('status', 'accepted')
                ->whereNotNull('student_id')->whereNotNull('target_student_id')
                ->where(fn ($query) => $query->whereIn('student_id', $frontier)->orWhereIn('target_student_id', $frontier))
                ->get(['student_id', 'target_student_id']);
            $nextIds = $links->pluck('student_id')->merge($links->pluck('target_student_id'))->unique()->all();
            $frontier = array_values(array_diff($nextIds, $relatedIds));
            $relatedIds = array_merge($relatedIds, $frontier);
        }
        $targets = Student::withTrashed()->where('school_id', $targetSchoolId)
            ->where(function ($query) use ($relatedIds, $source): void {
                $query->whereIn('id', $relatedIds);
                if ($source->user_id) {
                    $query->orWhere('user_id', $source->user_id);
                }
            })->lockForUpdate()->get();
        if ($targets->count() > 1 || $targets->first()?->trashed()) {
            throw ValidationException::withMessages(['transfer' => 'Data siswa lama di sekolah tujuan perlu diperiksa sebelum transfer dapat diterima.']);
        }
        $target = $targets->first();
        if ($target && $target->user_id && $target->user_id !== $source->user_id) {
            throw ValidationException::withMessages(['transfer' => 'Akun pada data siswa lama berbeda. Periksa tautan akun sebelum menerima transfer.']);
        }
        if (! $target) {
            if ($source->nisn && Student::query()->where('school_id', $targetSchoolId)->where('nisn', $source->nisn)->exists()) {
                throw ValidationException::withMessages(['transfer' => 'NISN sudah terdaftar di sekolah tujuan. Periksa data siswa tujuan sebelum mengulang transfer.']);
            }
            $target = $source->replicate();
            $target->school_id = $targetSchoolId;
            $target->nis = null;
            $target->status = 'active';
            $target->joined_at = now()->toDateString();
            $target->save();
        }
        $enrollment = StudentEnrollment::query()->where('student_id', $target->id)->where('academic_year_id', $yearId)->lockForUpdate()->first();
        if ($enrollment && (! in_array($target->id, $relatedIds, true) || ! in_array($enrollment->status, ['inactive', 'transferred'], true))) {
            throw ValidationException::withMessages(['transfer' => 'Penempatan tahun tujuan masih aktif atau sudah lulus. Periksa data sebelum menerima transfer.']);
        }
        if ($source->user_id && ! $target->user_id) {
            $target->update(['user_id' => $source->user_id]);
        }
        $placement = [
            'student_id' => $target->id, 'academic_year_id' => $yearId, 'classroom_id' => $classroomId,
            'status' => 'active', 'enrolled_at' => now()->toDateString(), 'completed_at' => null,
        ];
        if ($enrollment) {
            $transfer->previous_target_enrollment = $enrollment->only(['id', 'school_id', 'student_id', 'academic_year_id', 'classroom_id', 'status', 'enrolled_at', 'completed_at']);
            $enrollment->update($placement);
        } else {
            StudentEnrollment::query()->create($placement);
        }

        return $target;
    }
}
