<?php

namespace App\Livewire\Students;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ScoutLevel;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentScoutLevel;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $nis = '';

    public string $nisn = '';

    public string $name = '';

    public string $gender = '';

    public string $birth_place = '';

    public string $birth_date = '';

    public string $phone = '';

    public string $parent_phone = '';

    public string $address = '';

    public string $joined_at = '';

    public string $status = 'active';

    public ?int $academic_year_id = null;

    public ?int $classroom_id = null;

    public ?int $scout_level_id = null;

    public string $search = '';

    public function mount(): void
    {
        $academicYear = AcademicYear::query()
            ->where('is_active', true)
            ->first();

        $this->academic_year_id =
            $academicYear?->id;

        $this->joined_at =
            now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAcademicYearId(): void
    {
        $this->classroom_id = null;
        $this->scout_level_id = null;
    }

    protected function schoolId(): int
    {
        $schoolId = app(
            SchoolContext::class
        )->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        return $schoolId;
    }

    protected function rules(): array
    {
        $schoolId = $this->schoolId();

        return [
            'nis' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'students',
                    'nis'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'school_id',
                                $schoolId
                            )
                    )
                    ->ignore(
                        $this->editingId
                    ),
            ],

            'nisn' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique(
                    'students',
                    'nisn'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'school_id',
                                $schoolId
                            )
                    )
                    ->ignore(
                        $this->editingId
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'gender' => [
                'required',
                Rule::in([
                    'L',
                    'P',
                ]),
            ],

            'birth_place' => [
                'nullable',
                'string',
                'max:100',
            ],

            'birth_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'parent_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'joined_at' => [
                'nullable',
                'date',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                    'graduated',
                    'transferred',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Penempatan Sekolah
            |--------------------------------------------------------------------------
            */

            'academic_year_id' => [
                'required',
                'integer',

                Rule::exists(
                    'academic_years',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'school_id',
                            $schoolId
                        )
                ),
            ],

            'classroom_id' => [
                'required',
                'integer',

                Rule::exists(
                    'classrooms',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'school_id',
                            $schoolId
                        )
                ),
            ],

            'scout_level_id' => [
                'required',
                'integer',
                Rule::exists(
                    'scout_levels',
                    'id'
                ),
            ],
        ];
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()->can(
                $this->editingId
                    ? 'students.update'
                    : 'students.create'
            ),
            403
        );

        $validated = $this->validate();

        /*
        |--------------------------------------------------------------------------
        | Pastikan relasi berasal dari tenant aktif
        |--------------------------------------------------------------------------
        */

        $academicYear = AcademicYear::query()
            ->findOrFail(
                $validated[
                    'academic_year_id'
                ]
            );

        $classroom = Classroom::query()
            ->findOrFail(
                $validated[
                    'classroom_id'
                ]
            );

        $scoutLevel = ScoutLevel::query()
            ->findOrFail(
                $validated[
                    'scout_level_id'
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Normalisasi
        |--------------------------------------------------------------------------
        */

        $studentData = [
            'nis' => trim(
                $validated['nis']
            ),

            'nisn' => filled(
                $validated['nisn']
            )
                ? trim(
                    $validated['nisn']
                )
                : null,

            'name' => trim(
                $validated['name']
            ),

            'gender' =>
                $validated['gender'],

            'birth_place' => filled(
                $validated['birth_place']
            )
                ? trim(
                    $validated[
                        'birth_place'
                    ]
                )
                : null,

            'birth_date' =>
                $validated['birth_date']
                ?: null,

            'phone' => filled(
                $validated['phone']
            )
                ? trim(
                    $validated['phone']
                )
                : null,

            'parent_phone' => filled(
                $validated['parent_phone']
            )
                ? trim(
                    $validated[
                        'parent_phone'
                    ]
                )
                : null,

            'address' => filled(
                $validated['address']
            )
                ? trim(
                    $validated['address']
                )
                : null,

            'joined_at' =>
                $validated['joined_at']
                ?: null,

            'status' =>
                $validated['status'],
        ];

        /*
        |--------------------------------------------------------------------------
        | Satu transaksi
        |--------------------------------------------------------------------------
        |
        | students
        | student_enrollments
        | student_scout_levels
        |
        */

        DB::transaction(
            function () use (
                $studentData,
                $validated,
                $academicYear,
                $classroom,
                $scoutLevel
            ): void {

                /*
                |--------------------------------------------------------------------------
                | 1. Student
                |--------------------------------------------------------------------------
                */

                if ($this->editingId) {

                    $student = Student::query()
                        ->findOrFail(
                            $this->editingId
                        );

                    $student->update(
                        $studentData
                    );

                } else {

                    $student = Student::query()
                        ->create(
                            $studentData
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | 2. Enrollment Kelas
                |--------------------------------------------------------------------------
                */

                $enrollment =
                    StudentEnrollment::query()
                        ->firstOrNew([
                            'student_id' =>
                                $student->id,

                            'academic_year_id' =>
                                $academicYear->id,
                        ]);

                $enrollment->classroom_id =
                    $classroom->id;


                /*
                |--------------------------------------------------------------------------
                | Status Enrollment
                |--------------------------------------------------------------------------
                */

                if (
                    $student->status !==
                    'active'
                ) {
                    $enrollment->status =
                        'inactive';

                } elseif (
                    $academicYear->is_active
                ) {
                    $enrollment->status =
                        'active';

                } else {
                    $enrollment->status =
                        'completed';
                }


                /*
                |--------------------------------------------------------------------------
                | Tanggal Enrollment
                |--------------------------------------------------------------------------
                */

                if (
                    ! $enrollment->exists
                ) {
                    $enrollment->enrolled_at =
                        $student->joined_at
                            ?->format('Y-m-d')
                        ??
                        $academicYear
                            ->start_date;
                }


                if (
                    $enrollment->status ===
                    'completed'
                ) {
                    $enrollment->completed_at =
                        $academicYear
                            ->end_date;
                } else {
                    $enrollment->completed_at =
                        null;
                }

                $enrollment->save();


                /*
                |--------------------------------------------------------------------------
                | 3. Golongan Pramuka
                |--------------------------------------------------------------------------
                */

                if ($academicYear->is_active) {

                    StudentScoutLevel::query()
                        ->where(
                            'student_id',
                            $student->id
                        )
                        ->where(
                            'academic_year_id',
                            '!=',
                            $academicYear->id
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->update([
                            'is_active' => false,
                        ]);
                }


                $studentScoutLevel =
                    StudentScoutLevel::query()
                        ->firstOrNew([
                            'student_id' =>
                                $student->id,

                            'academic_year_id' =>
                                $academicYear->id,
                        ]);

                $studentScoutLevel
                    ->scout_level_id =
                        $scoutLevel->id;

                if (
                    ! $studentScoutLevel
                        ->exists
                ) {
                    $studentScoutLevel
                        ->started_at =
                            $academicYear
                                ->start_date;
                }

                $studentScoutLevel
                    ->is_active =
                        (bool)
                        $academicYear
                            ->is_active;

                $studentScoutLevel
                    ->ended_at =
                        $academicYear
                            ->is_active
                                ? null
                                : $academicYear
                                    ->end_date;

                $studentScoutLevel->save();
            }
        );

        session()->flash(
            'success',
            $this->editingId
                ? 'Data siswa berhasil diperbarui.'
                : 'Siswa berhasil ditambahkan.'
        );

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        abort_unless(
            auth()->user()->can(
                'students.update'
            ),
            403
        );

        $student = Student::query()
            ->findOrFail($id);

        $activeAcademicYear =
            AcademicYear::query()
                ->where(
                    'is_active',
                    true
                )
                ->first();

        /*
        |--------------------------------------------------------------------------
        | Prioritaskan enrollment tahun aktif
        |--------------------------------------------------------------------------
        */

        $enrollment =
            StudentEnrollment::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->when(
                    $activeAcademicYear,
                    fn ($query) =>
                        $query->where(
                            'academic_year_id',
                            $activeAcademicYear
                                ->id
                        )
                )
                ->first();

        /*
        |--------------------------------------------------------------------------
        | Jika tidak ada, ambil enrollment terakhir
        |--------------------------------------------------------------------------
        */

        if (! $enrollment) {

            $enrollment =
                StudentEnrollment::query()
                    ->where(
                        'student_id',
                        $student->id
                    )
                    ->orderByDesc(
                        'academic_year_id'
                    )
                    ->first();
        }

        $academicYearId =
            $enrollment
                ?->academic_year_id
            ??
            $activeAcademicYear
                ?->id;

        $scoutLevel =
            $academicYearId
                ? StudentScoutLevel::query()
                    ->where(
                        'student_id',
                        $student->id
                    )
                    ->where(
                        'academic_year_id',
                        $academicYearId
                    )
                    ->first()
                : null;


        /*
        |--------------------------------------------------------------------------
        | Isi Form
        |--------------------------------------------------------------------------
        */

        $this->editingId =
            $student->id;

        $this->nis =
            $student->nis ?? '';

        $this->nisn =
            $student->nisn ?? '';

        $this->name =
            $student->name;

        $this->gender =
            $student->gender ?? '';

        $this->birth_place =
            $student->birth_place ?? '';

        $this->birth_date =
            $student->birth_date
                ?->format('Y-m-d')
            ?? '';

        $this->phone =
            $student->phone ?? '';

        $this->parent_phone =
            $student->parent_phone ?? '';

        $this->address =
            $student->address ?? '';

        $this->joined_at =
            $student->joined_at
                ?->format('Y-m-d')
            ?? '';

        $this->status =
            $student->status
            ?? 'active';

        $this->academic_year_id =
            $academicYearId;

        $this->classroom_id =
            $enrollment
                ?->classroom_id;

        $this->scout_level_id =
            $scoutLevel
                ?->scout_level_id;

        $this->resetValidation();
    }

    public function toggleStatus(
        int $id
    ): void {
        abort_unless(
            auth()->user()->can(
                'students.toggle'
            ),
            403
        );

        DB::transaction(
            function () use ($id): void {

                $student =
                    Student::query()
                        ->findOrFail($id);

                /*
                |--------------------------------------------------------------------------
                | Lulus dan pindah tidak ditoggle otomatis
                |--------------------------------------------------------------------------
                */

                abort_unless(
                    in_array(
                        $student->status,
                        [
                            'active',
                            'inactive',
                        ],
                        true
                    ),
                    422,
                    'Status siswa ini harus diubah melalui menu Edit.'
                );

                $newStatus =
                    $student->status ===
                    'active'
                        ? 'inactive'
                        : 'active';

                $student->update([
                    'status' =>
                        $newStatus,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Sinkronkan enrollment tahun aktif
                |--------------------------------------------------------------------------
                */

                $activeYear =
                    AcademicYear::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->first();

                if ($activeYear) {

                    StudentEnrollment::query()
                        ->where(
                            'student_id',
                            $student->id
                        )
                        ->where(
                            'academic_year_id',
                            $activeYear->id
                        )
                        ->update([
                            'status' =>
                                $newStatus,
                        ]);
                }
            }
        );

        session()->flash(
            'success',
            'Status siswa berhasil diperbarui.'
        );
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $activeAcademicYear =
            AcademicYear::query()
                ->where(
                    'is_active',
                    true
                )
                ->first();

        $this->reset([
            'editingId',
            'nis',
            'nisn',
            'name',
            'gender',
            'birth_place',
            'birth_date',
            'phone',
            'parent_phone',
            'address',
            'classroom_id',
            'scout_level_id',
        ]);

        $this->academic_year_id =
            $activeAcademicYear?->id;

        $this->joined_at =
            now()->toDateString();

        $this->status =
            'active';

        $this->resetValidation();
    }

    public function render()
    {
        $academicYears =
            AcademicYear::query()
                ->orderByDesc(
                    'start_date'
                )
                ->get();

        $classrooms =
            Classroom::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('grade')
                ->orderBy('name')
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Scout Level bersifat global
        |--------------------------------------------------------------------------
        */

        $scoutLevels =
            ScoutLevel::query()
                ->orderBy(
                    'sort_order'
                )
                ->get();

        $activeAcademicYear =
            $academicYears
                ->firstWhere(
                    'is_active',
                    true
                );

        $activeYearId =
            $activeAcademicYear
                ?->id;

        /*
        |--------------------------------------------------------------------------
        | Siswa
        |--------------------------------------------------------------------------
        */

        $students =
            Student::query()
                ->with([
                    'enrollments' =>
                        function ($query) use (
                            $activeYearId
                        ): void {

                            if ($activeYearId) {
                                $query->where(
                                    'academic_year_id',
                                    $activeYearId
                                );
                            }

                            $query->with([
                                'classroom',
                                'academicYear',
                            ]);
                        },

                    'scoutLevelHistories' =>
                        function ($query) use (
                            $activeYearId
                        ): void {

                            if ($activeYearId) {
                                $query->where(
                                    'academic_year_id',
                                    $activeYearId
                                );
                            }

                            $query->with(
                                'scoutLevel'
                            );
                        },
                ])
                ->when(
                    $this->search,
                    function ($query): void {

                        $search =
                            '%' .
                            trim(
                                $this->search
                            ) .
                            '%';

                        $query->where(
                            function (
                                $query
                            ) use (
                                $search
                            ): void {

                                $query
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
                                    ->orWhere(
                                        'nisn',
                                        'like',
                                        $search
                                    )
                                    ->orWhere(
                                        'phone',
                                        'like',
                                        $search
                                    )
                                    ->orWhere(
                                        'parent_phone',
                                        'like',
                                        $search
                                    );
                            }
                        );
                    }
                )
                ->orderBy('name')
                ->paginate(10);

        return view(
            'livewire.students.index',
            compact(
                'students',
                'academicYears',
                'classrooms',
                'scoutLevels',
                'activeAcademicYear'
            )
        );
    }
}