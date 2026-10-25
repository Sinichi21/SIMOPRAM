<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StudentIndexRequest;
use App\Http\Requests\Api\V1\StudentSaveRequest;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ScoutLevel;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentScoutLevel;
use App\Services\UserLifecycleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index(StudentIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $academicYearId = $filters['academic_year_id'] ?? AcademicYear::query()->where('is_active', true)->value('id');
        $academicYearId = $academicYearId ? (int) $academicYearId : null;

        $students = Student::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search) use ($academicYearId): void {
                $search = '%'.trim($search).'%';
                $query->where(function (Builder $query) use ($search, $academicYearId): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('nis', 'like', $search)
                        ->orWhere('nisn', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('parent_phone', 'like', $search)
                        ->orWhereHas('enrollments', function (Builder $query) use ($search, $academicYearId): void {
                            $query->when($academicYearId, fn (Builder $query) => $query->where('academic_year_id', $academicYearId))
                                ->whereHas('classroom', fn (Builder $query) => $query->where('name', 'like', $search));
                        })
                        ->orWhereHas('scoutLevelHistories', function (Builder $query) use ($search, $academicYearId): void {
                            $query->when($academicYearId, fn (Builder $query) => $query->where('academic_year_id', $academicYearId))
                                ->whereHas('scoutLevel', fn (Builder $query) => $query->where('name', 'like', $search));
                        });
                });
            })
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['gender']), fn (Builder $query) => $query->where('gender', $filters['gender']))
            ->when($academicYearId || isset($filters['classroom_id']), function (Builder $query) use ($academicYearId, $filters): void {
                $query->whereHas('enrollments', function (Builder $query) use ($academicYearId, $filters): void {
                    $query->when($academicYearId, fn (Builder $query) => $query->where('academic_year_id', $academicYearId))
                        ->when(isset($filters['classroom_id']), fn (Builder $query) => $query->where('classroom_id', $filters['classroom_id']));
                });
            })
            ->when(isset($filters['scout_level_id']), function (Builder $query) use ($academicYearId, $filters): void {
                $query->whereHas('scoutLevelHistories', function (Builder $query) use ($academicYearId, $filters): void {
                    $query->where('scout_level_id', $filters['scout_level_id'])
                        ->when($academicYearId, fn (Builder $query) => $query->where('academic_year_id', $academicYearId));
                });
            });

        $students = $this->withPlacement($students, $academicYearId);

        return StudentResource::collection($students->orderBy('name')->paginate($request->integer('per_page', 20)));
    }

    public function show(int $studentId): StudentResource
    {
        $student = $this->withPlacement(Student::query()->whereKey($studentId), null)->firstOrFail();

        return new StudentResource($student);
    }

    public function store(StudentSaveRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $data = $this->studentData($validated);
        $academicYear = AcademicYear::query()->whereKey((int) $validated['academic_year_id'])->firstOrFail();
        $classroom = Classroom::query()->whereKey((int) $validated['classroom_id'])->firstOrFail();
        $scoutLevel = ScoutLevel::query()->whereKey((int) $validated['scout_level_id'])->firstOrFail();

        $student = DB::transaction(function () use ($data, $academicYear, $classroom, $scoutLevel): Student {
            $student = Student::query()->create($data);
            $this->savePlacement($student, $academicYear, $classroom, $scoutLevel);

            return $student;
        });

        $student = $this->withPlacement(Student::query()->whereKey($student->id), $academicYear->id)->firstOrFail();

        return (new StudentResource($student))->response()->setStatusCode(201);
    }

    public function update(StudentSaveRequest $request, int $studentId): StudentResource
    {
        $student = Student::query()->findOrFail($studentId);
        $validated = $request->validated();
        $data = $this->studentData($validated);
        $academicYear = AcademicYear::query()->whereKey((int) $validated['academic_year_id'])->firstOrFail();
        $classroom = Classroom::query()->whereKey((int) $validated['classroom_id'])->firstOrFail();
        $scoutLevel = ScoutLevel::query()->whereKey((int) $validated['scout_level_id'])->firstOrFail();

        DB::transaction(function () use ($student, $data, $academicYear, $classroom, $scoutLevel): void {
            $student->update($data);
            app(UserLifecycleService::class)->syncStudentMembership($student);
            $this->savePlacement($student, $academicYear, $classroom, $scoutLevel);
        });

        $student = $this->withPlacement(Student::query()->whereKey($student->id), $academicYear->id)->firstOrFail();

        return new StudentResource($student);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function studentData(array $validated): array
    {
        $data = Arr::only($validated, [
            'nis', 'nisn', 'name', 'gender', 'birth_place', 'birth_date',
            'phone', 'parent_phone', 'address', 'joined_at', 'status',
        ]);

        foreach (['nis', 'nisn', 'name', 'birth_place', 'phone', 'parent_phone', 'address'] as $field) {
            $data[$field] = filled($data[$field] ?? null) ? trim($data[$field]) : null;
        }

        $data['birth_date'] = $data['birth_date'] ?? null;
        $data['joined_at'] = $data['joined_at'] ?? null;

        return $data;
    }

    private function savePlacement(Student $student, AcademicYear $academicYear, Classroom $classroom, ScoutLevel $scoutLevel): void
    {
        $enrollment = StudentEnrollment::query()->firstOrNew([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $enrollment->classroom()->associate($classroom);
        $enrollment->status = $student->status !== 'active'
            ? 'inactive'
            : ($academicYear->is_active ? 'active' : 'completed');

        if (! $enrollment->exists) {
            $enrollment->enrolled_at = $student->joined_at
                ? Carbon::parse($student->joined_at)->toDateString()
                : $academicYear->start_date;
        }

        $enrollment->completed_at = $enrollment->status === 'completed' ? $academicYear->end_date : null;
        $enrollment->save();

        if ($academicYear->is_active) {
            StudentScoutLevel::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', '!=', $academicYear->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $scoutLevelHistory = StudentScoutLevel::query()->firstOrNew([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $scoutLevelHistory->scoutLevel()->associate($scoutLevel);

        if (! $scoutLevelHistory->exists) {
            $scoutLevelHistory->started_at = $academicYear->start_date;
        }

        $scoutLevelHistory->is_active = (bool) $academicYear->is_active;
        $scoutLevelHistory->ended_at = $academicYear->is_active ? null : $academicYear->end_date;
        $scoutLevelHistory->save();
    }

    /**
     * @param  Builder<Student>  $query
     * @return Builder<Student>
     */
    private function withPlacement(Builder $query, ?int $academicYearId): Builder
    {
        return $query->with([
            'enrollments' => function ($query) use ($academicYearId): void {
                $query->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
                    ->with(['academicYear', 'classroom'])
                    ->orderByDesc('academic_year_id');
            },
            'scoutLevelHistories' => function ($query) use ($academicYearId): void {
                $query->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
                    ->with('scoutLevel')
                    ->orderByDesc('academic_year_id');
            },
        ]);
    }
}
