<?php

namespace App\Livewire\Activities;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Coach;
use App\Models\ScoutLevel;
use App\Models\Semester;
use App\Services\ActivityHierarchyService;
use App\Services\ContentMediaService;
use App\Services\PublishedContentDocuments;
use App\Support\SchoolContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public array $attachmentUploads = [];

    public array $existingAttachments = [];

    public array $publishedDocumentIds = [];

    #[Computed]
    public function publishedDocumentOptions(): Collection
    {
        return app(PublishedContentDocuments::class)->available(app(SchoolContext::class)->id());
    }

    public array $removeAttachmentIndexes = [];

    public $bannerUpload;

    public bool $removeBanner = false;

    public ?int $editingId = null;

    public bool $confirmSessionChange = false;


    public ?int $parentActivityId = null;

    public ?int $academic_year_id = null;

    public ?int $semester_id = null;

    public string $title = '';

    public string $activity_type = 'regular';

    public ?int $routine_session_no = null;

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
            'parentActivityId' => ['nullable', 'integer', 'min:1'],
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

            'routine_session_no' => [
                Rule::requiredIf($this->activity_type === 'regular'),
                'nullable',
                'integer',
                'min:1',
                'max:10',
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

        if ($this->editingId) {
            $existing = Activity::query()->where('school_id', $schoolId)->findOrFail($this->editingId);
            $oldSession = $existing->activity_type === 'regular' ? (int) $existing->routine_session_no : null;
            $newSession = $validated['activity_type'] === 'regular' ? (int) $validated['routine_session_no'] : null;

            if ($oldSession !== $newSession && $existing->attendanceSessions()->whereHas('attendances')->exists()
                && ! $this->confirmSessionChange) {
                $this->addError('routine_session_no', 'Kegiatan sudah memiliki absensi. Centang konfirmasi perubahan sesi untuk melanjutkan. Riwayat absensi tetap dipertahankan.');

                return;
            }
        }

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

                    'routine_session_no' => $validated['activity_type'] === 'regular'
                        ? (int) $validated['routine_session_no']
                        : null,

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
                app(ActivityHierarchyService::class)->assignParent($activity, $this->parentActivityId);
                $activity->save();
                app(ContentMediaService::class)->save($activity, $this->attachmentUploads, $this->bannerUpload, $this->removeBanner, $this->removeAttachmentIndexes, documentIds: $this->publishedDocumentIds);

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
        $this->confirmSessionChange = false;
        $this->existingAttachments = collect($activity->attachments ?? [])->map(fn (array $file): array => ['name' => $file['name']])->all();
        $this->parentActivityId = $activity->parent_activity_id;
        $this->reset('publishedDocumentIds', 'attachmentUploads', 'removeAttachmentIndexes', 'bannerUpload', 'removeBanner');

        $this->academic_year_id =
            $activity->academic_year_id;

        $this->semester_id =
            $activity->semester_id;

        $this->title =
            $activity->title;

        $this->activity_type =
            $activity->activity_type;

        $this->routine_session_no =
            $activity->routine_session_no;

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
        $this->parentActivityId = null;
        $this->confirmSessionChange = false;
        $this->reset('publishedDocumentIds', 'attachmentUploads', 'existingAttachments', 'removeAttachmentIndexes', 'bannerUpload', 'removeBanner');
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

        $this->routine_session_no = null;

        $this->status =
            'draft';

        $this->is_public =
            false;

        $this->start_at = '';

        $this->end_at = '';

        $this->resetValidation();
    }

    public function updatedActivityType(string $activityType): void
    {
        if ($activityType !== 'regular') {
            $this->routine_session_no = null;
        }

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
        $parentActivities = Activity::query()->where('school_id', $this->schoolId())->whereNull('parent_activity_id')
            ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))->orderBy('title')->get(['id', 'title']);
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
                'parentActivities',
                'academicYears',
                'semesters',
                'coaches', 'scoutLevels'
            )
        );
    }
}
