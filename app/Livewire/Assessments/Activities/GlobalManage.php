<?php

namespace App\Livewire\Assessments\Activities;

use App\Models\ActivityAssessment;
use App\Services\ActivityAssessmentService;
use App\Services\GlobalActivityAccess;
use App\Services\PublicAssessmentService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GlobalManage extends Component
{
    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $selectedId = null;

    public ?int $activityId = null;

    public string $title = '';

    public string $mode = 'team';

    public string $participants = '';

    /** @var array<int, array{name: string, description: string, max_score: int|float|string, weight: int|float|string}> */
    public array $criteria = [['name' => '', 'description' => '', 'max_score' => 100, 'weight' => 100]];

    public function boot(): void
    {
        abort_unless(auth()->user() && app(GlobalActivityAccess::class)->canEnter(auth()->user()), 403);
        app(SchoolContext::class)->clear();
    }

    public function mount(?int $activityId = null): void
    {
        if ($activityId) {
            app(GlobalActivityAccess::class)->available(auth()->user())->findOrFail($activityId);
            $this->activityId = $activityId;
        }
    }

    protected function assessment(int $id): ActivityAssessment
    {
        return ActivityAssessment::withoutGlobalScope('school')->whereNull('school_id')->where('is_special', true)
            ->whereIn('activity_id', app(GlobalActivityAccess::class)->available(auth()->user())->select('activities.id'))->findOrFail($id);
    }

    public function addCriterion(): void
    {
        $this->criteria[] = ['name' => '', 'description' => '', 'max_score' => 100, 'weight' => 0];
    }

    public function useRegisteredParticipants(): void
    {
        $this->validate(['activityId' => ['required', 'integer']]);
        $activity = app(GlobalActivityAccess::class)->available(auth()->user())->findOrFail($this->activityId);
        $names = $activity->entries()->where('status', 'active')->where('validation_status', 'validated')
            ->when($this->mode === 'individual', fn ($query) => $query->where('category', 'individual'))
            ->when($this->mode === 'team', fn ($query) => $query->where('category', '!=', 'individual'))
            ->orderBy('name')->pluck('name');
        if ($names->isEmpty()) {
            throw ValidationException::withMessages(['participants' => 'Belum ada peserta terverifikasi yang sesuai mode penilaian.']);
        }
        $this->participants = $names->implode("\n");
    }

    public function removeCriterion(int $index): void
    {
        unset($this->criteria[$index]);
        $this->criteria = array_values($this->criteria);
    }

    public function save(): void
    {
        abort_unless(auth()->user() && app(GlobalActivityAccess::class)->canEnter(auth()->user()), 403);
        $this->validate([
            'activityId' => ['required', 'integer', Rule::exists('activities', 'id')->whereNull('school_id')->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:200'], 'mode' => ['required', 'in:individual,team'],
            'participants' => ['required', 'string', 'max:30000'],
            'criteria' => ['required', 'array', 'min:1', 'max:30'],
            'criteria.*.name' => ['required', 'string', 'max:150'],
            'criteria.*.description' => ['nullable', 'string', 'max:2000'],
            'criteria.*.max_score' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'criteria.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $names = collect(preg_split('/\R/u', trim($this->participants)))->map(fn (string $name): string => trim($name))->filter()->values();
        app(GlobalActivityAccess::class)->available(auth()->user())->findOrFail($this->activityId);
        validator(['names' => $names->all()], ['names' => ['required', 'array', 'max:200'], 'names.*' => ['required', 'string', 'max:150', 'distinct']])->validate();
        $assessment = DB::transaction(function () use ($names): ActivityAssessment {
            $assessment = $this->editingId ? $this->assessment($this->editingId)->newQuery()->whereNull('school_id')->lockForUpdate()->findOrFail($this->editingId) : new ActivityAssessment;
            if ($assessment->exists && $assessment->judges()->exists()) {
                throw ValidationException::withMessages(['participants' => 'Kriteria dan peserta dikunci setelah link juri dibuat.']);
            }
            $assessment->fill(['activity_id' => $this->activityId, 'title' => trim($this->title), 'mode' => $this->mode,
                'is_special' => true, 'status' => 'draft', 'published_at' => null, 'results_published_at' => null]);
            $assessment->school_id = null;
            if (! $assessment->exists) {
                $assessment->created_by = auth()->id();
            }
            $assessment->save();
            $assessment->criteria()->delete();
            $assessment->targets()->delete();
            foreach ($this->criteria as $index => $criterion) {
                $assessment->criteria()->create([...$criterion, 'sort_order' => $index + 1]);
            }
            foreach ($names as $name) {
                $assessment->targets()->create(['participant_name' => $name]);
            }

            return $assessment;
        });
        $this->editingId = $assessment->id;
        $this->selectedId = $assessment->id;
        session()->flash('status', 'Form tersimpan. Aktifkan form untuk membuat link juri.');
    }

    public function edit(int $id): void
    {
        $assessment = $this->assessment($id)->load('criteria', 'targets');
        $this->editingId = $id;
        $this->selectedId = $id;
        $this->activityId = $assessment->activity_id;
        $this->title = $assessment->title;
        $this->mode = $assessment->mode;
        $this->participants = $assessment->targets->pluck('participant_name')->implode("\n");
        $this->criteria = $assessment->criteria->map(fn ($criterion): array => [
            'name' => $criterion->name, 'description' => $criterion->description ?? '', 'max_score' => $criterion->max_score, 'weight' => $criterion->weight,
        ])->all();
        $this->resetValidation();
    }

    public function activate(int $id, ActivityAssessmentService $service): void
    {
        DB::transaction(function () use ($id, $service): void {
            $assessment = $this->assessment($id)->newQuery()->whereNull('school_id')->lockForUpdate()->findOrFail($id);
            $service->validateForPublish($assessment);
            if (! $assessment->targets()->exists()) {
                throw ValidationException::withMessages(['participants' => 'Tambahkan peserta terlebih dahulu.']);
            }
            $assessment->update(['status' => 'published', 'published_at' => now(), 'published_by' => auth()->id()]);
        });
        $this->selectedId = $id;
    }

    public function publishResults(int $id, bool $published, PublicAssessmentService $service): void
    {
        $service->setPublished($this->assessment($id), $published);
        $this->resetValidation();
        session()->flash('status', $published ? 'Hasil penilaian tampil di halaman kegiatan umum.' : 'Hasil penilaian disembunyikan.');
    }

    public function newForm(): void
    {
        $this->reset('editingId', 'selectedId', 'title', 'participants', 'criteria', 'mode');
        $this->resetValidation();
    }

    public function render(): View
    {
        $activities = app(GlobalActivityAccess::class)->available(auth()->user())->latest('start_at')->get();
        $assessments = ActivityAssessment::withoutGlobalScope('school')->whereNull('school_id')->where('is_special', true)
            ->whereIn('activity_id', $activities->modelKeys())
            ->with('activity')->withCount('judges')->latest('id')->get();
        $selected = $this->selectedId ? $assessments->firstWhere('id', $this->selectedId) : null;

        return view('livewire.assessments.activities.global-manage', compact('activities', 'assessments', 'selected'));
    }
}
