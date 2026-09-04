<?php

namespace App\Livewire\Activities;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Coach;
use App\Models\ScoutLevel;
use App\Models\Semester;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public ?int $academic_year_id = null;

    public ?int $semester_id = null;

    public string $title = '';

    public string $activity_type = 'regular';

    public string $description = '';

    public string $location = '';

    public string $start_at = '';

    public string $end_at = '';

    public string $status = 'draft';

    public bool $is_public = false;

    public array $coach_ids = [];

    public array $scout_level_ids = [];

    public string $search = '';

    public string $filterStatus = '';

    public string $filterScoutLevelId = '';

    public function mount(): void
    {
        $year = AcademicYear::query()
            ->where('is_active', true)
            ->first();

        $this->academic_year_id = $year?->id;

        $semester = Semester::query()
            ->where('is_active', true)
            ->first();

        $this->semester_id = $semester?->id;
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
            'academic_year_id' => [
                'required',
                'integer',

                Rule::exists(
                    'academic_years',
                    'id'
                )->where(
                    fn ($query) => $query->where(
                        'school_id',
                        $schoolId
                    )
                ),
            ],

            'semester_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'semesters',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'school_id',
                            $schoolId
                        )
                        ->where(
                            'academic_year_id',
                            $this->academic_year_id
                        )
                ),
            ],

            'title' => [
                'required',
                'string',
                'max:200',
            ],

            'activity_type' => [
                'required',
                Rule::in([
                    'regular',
                    'training',
                    'ceremony',
                    'camp',
                    'competition',
                    'service',
                    'other',
                ]),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'start_at' => [
                'required',
                'date',
            ],

            'end_at' => [
                'required',
                'date',
                'after:start_at',
            ],

            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'published',
                    'completed',
                    'cancelled',
                ]),
            ],

            'is_public' => [
                'boolean',
            ],

            'coach_ids' => [
                'array',
            ],

            'coach_ids.*' => [
                'integer',

                Rule::exists(
                    'coaches',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'school_id',
                            $schoolId
                        )
                        ->where(
                            'is_active',
                            true
                        )
                ),
            ],

            'scout_level_ids' => [
                'array',
            ],

            'scout_level_ids.*' => [
                'integer',
                Rule::exists('scout_levels', 'id'),
            ],
        ];
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()->can(
                $this->editingId
                    ? 'activities.update'
                    : 'activities.create'
            ),
            403
        );

        $validated = $this->validate();

        $schoolId = $this->schoolId();

        DB::transaction(
            function () use (
                $validated,
                $schoolId
            ): void {

                $data = [
                    'academic_year_id' => $validated['academic_year_id'],

                    'semester_id' => $validated['semester_id']
                        ?: null,

                    'title' => trim($validated['title']),

                    'activity_type' => $validated['activity_type'],

                    'description' => filled($validated['description'])
                            ? trim($validated['description'])
                            : null,

                    'location' => filled($validated['location'])
                            ? trim($validated['location'])
                            : null,

                    'start_at' => $validated['start_at'],

                    'end_at' => $validated['end_at'],

                    'status' => $validated['status'],

                    'is_public' => $validated['is_public'],

                    'published_at' => $validated['status'] ===
                        'published'
                            ? now()
                            : null,
                ];

                if ($this->editingId) {
                    $activity = Activity::query()
                        ->findOrFail(
                            $this->editingId
                        );

                    $activity->update($data);
                } else {
                    $data['created_by'] =
                        auth()->id();

                    $activity = Activity::query()
                        ->create($data);
                }

                $syncData = [];

                foreach (
                    $validated['coach_ids'] as $coachId
                ) {
                    $syncData[$coachId] = [
                        'school_id' => $schoolId,
                        'role' => 'coach',
                    ];
                }

                $activity
                    ->coaches()
                    ->sync($syncData);

                $activity
                    ->scoutLevels()
                    ->sync($validated['scout_level_ids']);
            }
        );

        session()->flash(
            'success',
            $this->editingId
                ? 'Agenda berhasil diperbarui.'
                : 'Agenda berhasil dibuat.'
        );

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        abort_unless(
            auth()->user()->can(
                'activities.update'
            ),
            403
        );

        $activity = Activity::query()
            ->with([
                'coaches',
                'scoutLevels',
            ])
            ->findOrFail($id);

        $this->editingId =
            $activity->id;

        $this->academic_year_id =
            $activity->academic_year_id;

        $this->semester_id =
            $activity->semester_id;

        $this->title =
            $activity->title;

        $this->activity_type =
            $activity->activity_type;

        $this->description =
            $activity->description ?? '';

        $this->location =
            $activity->location ?? '';

        $this->start_at =
            $activity->start_at
                ->format('Y-m-d\TH:i');

        $this->end_at =
            $activity->end_at
                ->format('Y-m-d\TH:i');

        $this->status =
            $activity->status;

        $this->is_public =
            $activity->is_public;

        $this->coach_ids =
            $activity
                ->coaches
                ->pluck('id')
                ->map(
                    fn ($id) => (string) $id
                )
                ->toArray();

        $this->scout_level_ids =
            $activity
                ->scoutLevels
                ->pluck('id')
                ->map(fn ($id): string => (string) $id)
                ->all();

        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function cancelActivity(
        int $id
    ): void {
        abort_unless(
            auth()->user()->can(
                'activities.cancel'
            ),
            403
        );

        $activity = Activity::query()
            ->findOrFail($id);

        $activity->update([
            'status' => 'cancelled',
        ]);

        session()->flash(
            'success',
            'Agenda berhasil dibatalkan.'
        );
    }

    protected function resetForm(): void
    {
        $activeYear = AcademicYear::query()
            ->where('is_active', true)
            ->first();

        $activeSemester =
            Semester::query()
                ->where('is_active', true)
                ->first();

        $this->reset([
            'editingId',
            'title',
            'description',
            'location',
            'coach_ids',
            'scout_level_ids',
        ]);

        $this->academic_year_id =
            $activeYear?->id;

        $this->semester_id =
            $activeSemester?->id;

        $this->activity_type =
            'regular';

        $this->status =
            'draft';

        $this->is_public =
            false;

        $this->start_at = '';

        $this->end_at = '';

        $this->resetValidation();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterScoutLevelId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $academicYears =
            AcademicYear::query()
                ->orderByDesc('start_date')
                ->get();

        $semesters =
            Semester::query()
                ->when(
                    $this->academic_year_id,
                    fn ($query) => $query->where(
                        'academic_year_id',
                        $this->academic_year_id
                    )
                )
                ->orderBy('semester_number')
                ->get();

        $coaches =
            Coach::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

        $scoutLevels =
            ScoutLevel::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

        $activities =
            Activity::query()
                ->with([
                    'academicYear',
                    'semester',
                    'coaches',
                    'scoutLevels',
                ])
                ->when(
                    $this->search,
                    function ($query): void {
                        $search =
                            '%'.
                            trim($this->search).
                            '%';

                        $query->where(
                            function ($query) use ($search) {
                                $query
                                    ->where(
                                        'title',
                                        'like',
                                        $search
                                    )
                                    ->orWhere(
                                        'location',
                                        'like',
                                        $search
                                    );
                            }
                        );
                    }
                )
                ->when(
                    $this->filterStatus,
                    fn ($query) => $query->where(
                        'status',
                        $this->filterStatus
                    )
                )
                ->when(
                    $this->filterScoutLevelId,
                    function ($query): void {
                        $query->where(
                            function ($query): void {
                                $query
                                    ->whereDoesntHave('scoutLevels')
                                    ->orWhereHas(
                                        'scoutLevels',
                                        fn ($query) => $query->whereKey(
                                            (int) $this->filterScoutLevelId
                                        )
                                    );
                            }
                        );
                    }
                )
                ->orderByDesc('start_at')
                ->paginate(10);

        return view(
            'livewire.activities.index',
            compact(
                'activities',
                'academicYears',
                'semesters',
                'coaches', 'scoutLevels'
            )
        );
    }
}
