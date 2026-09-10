<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\SchoolUserMembership;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentProgressionService
{
    /** @param array<int, int|string> $studentIds */
    public function process(int $sourceYearId, int $sourceClassId, array $studentIds, string $action, ?int $targetYearId, ?int $targetClassId): int
    {
        abort_unless(auth()->user()?->can('students.update'), 403);
        $school = app(SchoolContext::class)->school();
        abort_unless($school, 409);
        abort_unless(in_array($action, ['promote', 'repeat', 'graduate'], true), 422);
        $sourceYear = AcademicYear::query()->where('school_id', $school->id)->findOrFail($sourceYearId);
        $sourceClass = Classroom::query()->where('school_id', $school->id)->findOrFail($sourceClassId);
        $ids = array_values(array_unique(array_map('intval', $studentIds)));
        if ($ids === []) {
            throw ValidationException::withMessages(['studentIds' => 'Pilih siswa yang akan diproses.']);
        }
        $finalGrade = match ($school->level) {
            'SD', 'MI' => 6, 'SMP', 'MTs' => 9, 'SMA', 'SMK', 'MA' => 12, default => null,
        };
        if ($action === 'promote' && $finalGrade !== null && (int) $sourceClass->grade >= $finalGrade) {
            throw ValidationException::withMessages(['action' => 'Siswa kelas akhir diproses sebagai lulusan, kemudian ditransfer ke sekolah tujuan.']);
        }
        if ($action === 'graduate') {
            if ($finalGrade === null || (int) $sourceClass->grade !== $finalGrade) {
                throw ValidationException::withMessages(['action' => 'Kelulusan hanya untuk kelas akhir sesuai jenjang sekolah (6, 9, atau 12).']);
            }
        } else {
            $targetYear = AcademicYear::query()->where('school_id', $school->id)->findOrFail($targetYearId);
            $targetClass = Classroom::query()->where('school_id', $school->id)->where('is_active', true)->findOrFail($targetClassId);
            if ($targetYear->start_date->lte($sourceYear->end_date)) {
                throw ValidationException::withMessages(['targetYearId' => 'Tahun tujuan harus setelah tahun ajaran asal.']);
            }
            $expectedGrade = (int) $sourceClass->grade + ($action === 'promote' ? 1 : 0);
            if ((int) $targetClass->grade !== $expectedGrade) {
                throw ValidationException::withMessages(['targetClassId' => 'Tingkat kelas tujuan tidak sesuai tindakan yang dipilih.']);
            }
        }

        return DB::transaction(function () use ($ids, $sourceYearId, $sourceClassId, $action, $targetYearId, $targetClassId, $school): int {
            $students = Student::query()->where('school_id', $school->id)->whereIn('id', $ids)
                ->where('status', 'active')->orderBy('id')->lockForUpdate()->get();
            if ($students->count() !== count($ids)) {
                throw ValidationException::withMessages(['studentIds' => 'Pilihan siswa tidak valid atau sudah tidak aktif. Muat ulang daftar.']);
            }
            foreach ($students as $student) {
                $enrollment = StudentEnrollment::query()->where('student_id', $student->id)
                    ->where('academic_year_id', $sourceYearId)->where('classroom_id', $sourceClassId)
                    ->where('status', 'active')->lockForUpdate()->first();
                if (! $enrollment) {
                    throw ValidationException::withMessages(['studentIds' => 'Penempatan siswa berubah atau sudah diproses. Muat ulang daftar.']);
                }
                if ($action !== 'graduate') {
                    if (StudentEnrollment::query()->where('student_id', $student->id)->where('academic_year_id', $targetYearId)->exists()) {
                        throw ValidationException::withMessages(['studentIds' => 'Siswa sudah memiliki penempatan pada tahun ajaran tujuan.']);
                    }
                    StudentEnrollment::query()->create([
                        'student_id' => $student->id, 'academic_year_id' => $targetYearId,
                        'classroom_id' => $targetClassId, 'status' => 'active', 'enrolled_at' => now()->toDateString(),
                    ]);
                } else {
                    $student->update(['status' => 'graduated']);
                    if ($student->user_id) {
                        $user = User::query()->lockForUpdate()->findOrFail($student->user_id);
                        SchoolUserMembership::query()->where('school_id', $school->id)->where('user_id', $user->id)
                            ->update(['is_active' => false, 'left_at' => now()->toDateString(), 'exit_reason' => 'graduated']);
                        $previousTeam = getPermissionsTeamId();
                        try {
                            setPermissionsTeamId($school->id);
                            $user->unsetRelation('roles')->unsetRelation('permissions')->syncRoles([]);
                        } finally {
                            setPermissionsTeamId($previousTeam);
                        }
                    }
                }
                $enrollment->update(['status' => $action === 'graduate' ? 'graduated' : 'completed', 'completed_at' => now()->toDateString()]);
            }

            return $students->count();
        });
    }
}
