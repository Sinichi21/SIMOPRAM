<?php

namespace App\Livewire\ScoutUnits;

use App\Models\AcademicYear;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\ScoutUnitMember;
use App\Models\Student;
use App\Models\StudentScoutLevel;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /*
    |--------------------------------------------------------------------------
    | Unit Form
    |--------------------------------------------------------------------------
    */

    public ?int $editingId = null;

    public ?int $academic_year_id = null;

    public ?int $scout_level_id = null;

    public string $name = '';

    public string $description = '';

    public bool $is_active = true;


    /*
    |--------------------------------------------------------------------------
    | Filter
    |--------------------------------------------------------------------------
    */

    public string $search = '';

    public ?int $filterAcademicYearId = null;

    public ?int $filterScoutLevelId = null;


    /*
    |--------------------------------------------------------------------------
    | Member Management
    |--------------------------------------------------------------------------
    */

    public ?int $selectedUnitId = null;

    public ?int $memberStudentId = null;

    public string $memberPosition = 'member';

    public string $memberJoinedAt = '';


    public function mount(): void
    {
        $activeYear = AcademicYear::query()
            ->where('is_active', true)
            ->first();

        $this->academic_year_id =
            $activeYear?->id;

        $this->filterAcademicYearId =
            $activeYear?->id;

        $this->memberJoinedAt =
            now()->toDateString();
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


    /*
    |--------------------------------------------------------------------------
    | Unit Type
    |--------------------------------------------------------------------------
    */

    protected function unitTypeForLevel(
        ScoutLevel $level
    ): string {
        return match (
            strtoupper($level->code)
        ) {
            'SIAGA' => 'barung',

            'PENGGALANG' => 'regu',

            'PENEGAK' => 'sangga',

            'PANDEGA' => 'racana',

            default => 'unit',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Unit
    |--------------------------------------------------------------------------
    */

    protected function unitRules(): array
    {
        $schoolId = $this->schoolId();

        return [
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

            'scout_level_id' => [
                'required',
                'integer',

                Rule::exists(
                    'scout_levels',
                    'id'
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'scout_units',
                    'name'
                )
                    ->where(
                        fn ($query) =>
                            $query
                                ->where(
                                    'school_id',
                                    $schoolId
                                )
                                ->where(
                                    'academic_year_id',
                                    $this->academic_year_id
                                )
                                ->where(
                                    'scout_level_id',
                                    $this->scout_level_id
                                )
                    )
                    ->ignore(
                        $this->editingId
                    ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Save Unit
    |--------------------------------------------------------------------------
    */

    public function saveUnit(): void
    {
        abort_unless(
            auth()->user()->can(
                'scout_units.manage'
            ),
            403
        );

        $validated = $this->validate(
            $this->unitRules()
        );

        $academicYear =
            AcademicYear::query()
                ->findOrFail(
                    $validated[
                        'academic_year_id'
                    ]
                );

        $scoutLevel =
            ScoutLevel::query()
                ->findOrFail(
                    $validated[
                        'scout_level_id'
                    ]
                );

        $unitType =
            $this->unitTypeForLevel(
                $scoutLevel
            );

        DB::transaction(
            function () use (
                $validated,
                $academicYear,
                $scoutLevel,
                $unitType
            ): void {

                $data = [
                    'academic_year_id' =>
                        $academicYear->id,

                    'scout_level_id' =>
                        $scoutLevel->id,

                    'name' =>
                        trim(
                            $validated['name']
                        ),

                    'unit_type' =>
                        $unitType,

                    'description' =>
                        filled(
                            $validated[
                                'description'
                            ]
                        )
                            ? trim(
                                $validated[
                                    'description'
                                ]
                            )
                            : null,

                    'is_active' =>
                        $validated[
                            'is_active'
                        ],
                ];

                if ($this->editingId) {

                    $unit = ScoutUnit::query()
                        ->findOrFail(
                            $this->editingId
                        );

                    $unit->update(
                        $data
                    );

                } else {

                    $unit = ScoutUnit::query()
                        ->create(
                            $data
                        );

                    $this->selectedUnitId =
                        $unit->id;
                }
            }
        );

        session()->flash(
            'success',
            $this->editingId
                ? 'Regu/Barung berhasil diperbarui.'
                : 'Regu/Barung berhasil ditambahkan.'
        );

        $this->resetUnitForm();
    }


    public function editUnit(int $id): void
    {
        abort_unless(
            auth()->user()->can(
                'scout_units.manage'
            ),
            403
        );

        $unit = ScoutUnit::query()
            ->findOrFail($id);

        $this->editingId =
            $unit->id;

        $this->academic_year_id =
            $unit->academic_year_id;

        $this->scout_level_id =
            $unit->scout_level_id;

        $this->name =
            $unit->name;

        $this->description =
            $unit->description ?? '';

        $this->is_active =
            (bool) $unit->is_active;

        $this->resetValidation();
    }


    public function cancelUnitEdit(): void
    {
        $this->resetUnitForm();
    }


    protected function resetUnitForm(): void
    {
        $activeYear = AcademicYear::query()
            ->where(
                'is_active',
                true
            )
            ->first();

        $this->reset([
            'editingId',
            'scout_level_id',
            'name',
            'description',
        ]);

        $this->academic_year_id =
            $activeYear?->id;

        $this->is_active = true;

        $this->resetValidation();
    }


    /*
    |--------------------------------------------------------------------------
    | Toggle Unit
    |--------------------------------------------------------------------------
    */

    public function toggleUnitStatus(
        int $id
    ): void {
        abort_unless(
            auth()->user()->can(
                'scout_units.manage'
            ),
            403
        );

        $unit = ScoutUnit::query()
            ->findOrFail($id);

        $unit->update([
            'is_active' =>
                ! $unit->is_active,
        ]);

        session()->flash(
            'success',
            'Status Regu/Barung berhasil diperbarui.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Select Unit
    |--------------------------------------------------------------------------
    */

    public function selectUnit(int $id): void
    {
        ScoutUnit::query()
            ->findOrFail($id);

        $this->selectedUnitId = $id;

        $this->memberStudentId = null;

        $this->memberPosition =
            'member';

        $this->memberJoinedAt =
            now()->toDateString();

        $this->resetValidation();
    }


    /*
    |--------------------------------------------------------------------------
    | Add Member
    |--------------------------------------------------------------------------
    */

    public function addMember(): void
    {
        abort_unless(
            auth()->user()->can(
                'scout_units.manage'
            ),
            403
        );

        $schoolId = $this->schoolId();

        $this->validate([
            'selectedUnitId' => [
                'required',
                'integer',
            ],

            'memberStudentId' => [
                'required',
                'integer',

                Rule::exists(
                    'students',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'school_id',
                            $schoolId
                        )
                ),
            ],

            'memberPosition' => [
                'required',
                Rule::in([
                    'leader',
                    'deputy',
                    'member',
                ]),
            ],

            'memberJoinedAt' => [
                'required',
                'date',
            ],
        ]);

        $unit = ScoutUnit::query()
            ->findOrFail(
                $this->selectedUnitId
            );

        $student = Student::query()
            ->findOrFail(
                $this->memberStudentId
            );


        /*
        |--------------------------------------------------------------------------
        | Pastikan Golongan Siswa Sesuai Unit
        |--------------------------------------------------------------------------
        */

        $hasCorrectLevel =
            StudentScoutLevel::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->where(
                    'academic_year_id',
                    $unit->academic_year_id
                )
                ->where(
                    'scout_level_id',
                    $unit->scout_level_id
                )
                ->exists();

        abort_unless(
            $hasCorrectLevel,
            422,
            'Golongan Pramuka siswa tidak sesuai dengan Regu/Barung.'
        );


        /*
        |--------------------------------------------------------------------------
        | Jangan Sampai Aktif di Dua Unit Pada Tahun Yang Sama
        |--------------------------------------------------------------------------
        */

        $otherMembership =
            ScoutUnitMember::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->whereNull(
                    'left_at'
                )
                ->whereHas(
                    'scoutUnit',
                    function ($query) use (
                        $unit
                    ): void {

                        $query
                            ->where(
                                'academic_year_id',
                                $unit
                                    ->academic_year_id
                            )
                            ->where(
                                'id',
                                '!=',
                                $unit->id
                            );
                    }
                )
                ->exists();

        abort_if(
            $otherMembership,
            422,
            'Siswa sudah menjadi anggota unit lain pada tahun ajaran ini.'
        );


        DB::transaction(
            function () use (
                $unit,
                $student
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Hanya Satu Pemimpin
                |--------------------------------------------------------------------------
                */

                if (
                    $this->memberPosition ===
                    'leader'
                ) {
                    ScoutUnitMember::query()
                        ->where(
                            'scout_unit_id',
                            $unit->id
                        )
                        ->where(
                            'position',
                            'leader'
                        )
                        ->whereNull(
                            'left_at'
                        )
                        ->update([
                            'position' =>
                                'member',
                        ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Hanya Satu Wakil
                |--------------------------------------------------------------------------
                */

                if (
                    $this->memberPosition ===
                    'deputy'
                ) {
                    ScoutUnitMember::query()
                        ->where(
                            'scout_unit_id',
                            $unit->id
                        )
                        ->where(
                            'position',
                            'deputy'
                        )
                        ->whereNull(
                            'left_at'
                        )
                        ->update([
                            'position' =>
                                'member',
                        ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Buat / Aktifkan Membership
                |--------------------------------------------------------------------------
                */

                $membership =
                    ScoutUnitMember::query()
                        ->firstOrNew([
                            'scout_unit_id' =>
                                $unit->id,

                            'student_id' =>
                                $student->id,
                        ]);

                if (
                    ! $membership->exists ||
                    $membership->left_at
                ) {
                    $membership->joined_at =
                        $this->memberJoinedAt;
                }

                $membership->position =
                    $this->memberPosition;

                $membership->left_at = null;

                $membership->save();


                /*
                |--------------------------------------------------------------------------
                | Leader Unit
                |--------------------------------------------------------------------------
                */

                if (
                    $this->memberPosition ===
                    'leader'
                ) {
                    $unit->update([
                        'leader_student_id' =>
                            $student->id,
                    ]);
                }
            }
        );

        $this->memberStudentId = null;

        $this->memberPosition =
            'member';

        session()->flash(
            'success',
            'Siswa berhasil ditambahkan ke Regu/Barung.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Change Member Position
    |--------------------------------------------------------------------------
    */

    public function changeMemberPosition(
        int $membershipId,
        string $position
    ): void {
        abort_unless(
            auth()->user()->can(
                'scout_units.manage'
            ),
            403
        );

        abort_unless(
            in_array(
                $position,
                [
                    'leader',
                    'deputy',
                    'member',
                ],
                true
            ),
            422
        );

        DB::transaction(
            function () use (
                $membershipId,
                $position
            ): void {

                $membership =
                    ScoutUnitMember::query()
                        ->whereNull(
                            'left_at'
                        )
                        ->findOrFail(
                            $membershipId
                        );

                $unit = ScoutUnit::query()
                    ->findOrFail(
                        $membership
                            ->scout_unit_id
                    );


                if ($position === 'leader') {

                    ScoutUnitMember::query()
                        ->where(
                            'scout_unit_id',
                            $unit->id
                        )
                        ->where(
                            'id',
                            '!=',
                            $membership->id
                        )
                        ->where(
                            'position',
                            'leader'
                        )
                        ->whereNull(
                            'left_at'
                        )
                        ->update([
                            'position' =>
                                'member',
                        ]);

                    $unit->leader_student_id =
                        $membership
                            ->student_id;
                }


                if ($position === 'deputy') {

                    ScoutUnitMember::query()
                        ->where(
                            'scout_unit_id',
                            $unit->id
                        )
                        ->where(
                            'id',
                            '!=',
                            $membership->id
                        )
                        ->where(
                            'position',
                            'deputy'
                        )
                        ->whereNull(
                            'left_at'
                        )
                        ->update([
                            'position' =>
                                'member',
                        ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Jika mantan leader diturunkan
                |--------------------------------------------------------------------------
                */

                if (
                    $position !== 'leader'
                    &&
                    (int)
                    $unit->leader_student_id
                    ===
                    (int)
                    $membership->student_id
                ) {
                    $unit->leader_student_id =
                        null;
                }

                $membership->position =
                    $position;

                $membership->save();

                $unit->save();
            }
        );

        session()->flash(
            'success',
            'Jabatan anggota berhasil diperbarui.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Member
    |--------------------------------------------------------------------------
    */

    public function removeMember(
        int $membershipId
    ): void {
        abort_unless(
            auth()->user()->can(
                'scout_units.manage'
            ),
            403
        );

        DB::transaction(
            function () use (
                $membershipId
            ): void {

                $membership =
                    ScoutUnitMember::query()
                        ->whereNull(
                            'left_at'
                        )
                        ->findOrFail(
                            $membershipId
                        );

                $unit = ScoutUnit::query()
                    ->findOrFail(
                        $membership
                            ->scout_unit_id
                    );

                $membership->left_at =
                    now()->toDateString();

                $membership->save();

                if (
                    (int)
                    $unit->leader_student_id
                    ===
                    (int)
                    $membership->student_id
                ) {
                    $unit->update([
                        'leader_student_id' =>
                            null,
                    ]);
                }
            }
        );

        session()->flash(
            'success',
            'Siswa dikeluarkan dari Regu/Barung.'
        );
    }


    public function updatedSearch(): void
    {
        $this->resetPage();
    }


    public function updatedFilterAcademicYearId(): void
    {
        $this->resetPage();

        $this->selectedUnitId =
            null;
    }


    public function updatedFilterScoutLevelId(): void
    {
        $this->resetPage();

        $this->selectedUnitId =
            null;
    }


    /*
    |--------------------------------------------------------------------------
    | Position Labels
    |--------------------------------------------------------------------------
    */

    protected function positionOptions(
        ?string $unitType
    ): array {
        return match ($unitType) {

            'barung' => [
                'leader' =>
                    'Pemimpin Barung',

                'deputy' =>
                    'Wakil Pemimpin Barung',

                'member' =>
                    'Anggota',
            ],

            'regu' => [
                'leader' =>
                    'Pemimpin Regu',

                'deputy' =>
                    'Wakil Pemimpin Regu',

                'member' =>
                    'Anggota',
            ],

            'sangga' => [
                'leader' =>
                    'Pemimpin Sangga',

                'deputy' =>
                    'Wakil Pemimpin Sangga',

                'member' =>
                    'Anggota',
            ],

            'racana' => [
                'leader' =>
                    'Ketua',

                'deputy' =>
                    'Wakil Ketua',

                'member' =>
                    'Anggota',
            ],

            default => [
                'leader' =>
                    'Pemimpin',

                'deputy' =>
                    'Wakil',

                'member' =>
                    'Anggota',
            ],
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        $academicYears =
            AcademicYear::query()
                ->orderByDesc(
                    'start_date'
                )
                ->get();

        $scoutLevels =
            ScoutLevel::query()
                ->orderBy(
                    'sort_order'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Unit List
        |--------------------------------------------------------------------------
        */

        $units =
            ScoutUnit::query()
                ->with([
                    'academicYear',
                    'scoutLevel',
                    'leader',
                ])
                ->withCount([
                    'memberships as active_members_count'
                        => fn ($query) =>
                            $query->whereNull(
                                'left_at'
                            ),
                ])
                ->when(
                    $this->filterAcademicYearId,
                    fn ($query) =>
                        $query->where(
                            'academic_year_id',
                            $this
                                ->filterAcademicYearId
                        )
                )
                ->when(
                    $this->filterScoutLevelId,
                    fn ($query) =>
                        $query->where(
                            'scout_level_id',
                            $this
                                ->filterScoutLevelId
                        )
                )
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
                            'name',
                            'like',
                            $search
                        );
                    }
                )
                ->orderBy('name')
                ->paginate(10);


        /*
        |--------------------------------------------------------------------------
        | Selected Unit
        |--------------------------------------------------------------------------
        */

        $selectedUnit = null;

        $eligibleStudents = collect();

        $positionOptions = [];


        if ($this->selectedUnitId) {

            $selectedUnit =
                ScoutUnit::query()
                    ->with([
                        'academicYear',
                        'scoutLevel',
                        'leader',

                        'memberships' =>
                            function ($query): void {

                                $query
                                    ->whereNull(
                                        'left_at'
                                    )
                                    ->with(
                                        'student'
                                    )
                                    ->orderBy(
                                        'position'
                                    );
                            },
                    ])
                    ->find(
                        $this
                            ->selectedUnitId
                    );


            if ($selectedUnit) {

                /*
                |--------------------------------------------------------------------------
                | Siswa Sesuai Golongan
                |--------------------------------------------------------------------------
                */

                $eligibleStudents =
                    Student::query()
                        ->where(
                            'status',
                            'active'
                        )
                        ->whereHas(
                            'scoutLevelHistories',
                            function (
                                $query
                            ) use (
                                $selectedUnit
                            ): void {

                                $query
                                    ->where(
                                        'academic_year_id',
                                        $selectedUnit
                                            ->academic_year_id
                                    )
                                    ->where(
                                        'scout_level_id',
                                        $selectedUnit
                                            ->scout_level_id
                                    );
                            }
                        )

                        /*
                        |--------------------------------------------------------------------------
                        | Belum Aktif di Unit Lain Tahun Ini
                        |--------------------------------------------------------------------------
                        */

                        ->whereDoesntHave(
                            'scoutUnitMembers',
                            function (
                                $query
                            ) use (
                                $selectedUnit
                            ): void {

                                $query
                                    ->whereNull(
                                        'left_at'
                                    )
                                    ->whereHas(
                                        'scoutUnit',
                                        function (
                                            $query
                                        ) use (
                                            $selectedUnit
                                        ): void {

                                            $query->where(
                                                'academic_year_id',
                                                $selectedUnit
                                                    ->academic_year_id
                                            );
                                        }
                                    );
                            }
                        )
                        ->orderBy('name')
                        ->get();

                $positionOptions =
                    $this->positionOptions(
                        $selectedUnit
                            ->unit_type
                    );
            }
        }


        return view(
            'livewire.scout-units.index',
            compact(
                'units',
                'academicYears',
                'scoutLevels',
                'selectedUnit',
                'eligibleStudents',
                'positionOptions'
            )
        );
    }
}