<?php

namespace App\Livewire\Admin;

use App\Models\Activity;
use App\Models\Announcement;
use App\Models\School;
use App\Services\ActivityHierarchyService;
use App\Services\ContentMediaService;
use App\Services\GlobalActivityAccess;
use App\Services\PublishedContentDocuments;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PublicContent extends Component
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

    #[Locked]
    public string $kind = 'activities';

    #[Locked]
    public ?int $editingId = null;

    public ?int $parentActivityId = null;

    public string $title = '';

    public string $body = '';

    public string $location = '';

    public string $activityType = 'competition';

    public string $startsAt = '';

    public string $endsAt = '';

    public string $publishedAt = '';

    public string $expiresAt = '';

    public string $status = 'draft';

    public string $search = '';

    public ?int $requestSchoolId = null;

    public bool $registrationOpen = false;

    public string $rejectionReason = '';

    public function boot(): void
    {
        abort_unless(auth()->user() && app(GlobalActivityAccess::class)->canEnter(auth()->user()), 403);
        app(SchoolContext::class)->clear();
    }

    public function mount(string $kind = 'activities'): void
    {
        abort_unless(in_array($kind, ['activities', 'announcements'], true), 404);
        $this->kind = $kind;
        if ($kind === 'announcements') {
            abort_unless(auth()->user()->isSuperAdmin(), 403);
        }
        $schoolIds = app(GlobalActivityAccess::class)->schoolAdminIds(auth()->user());
        $this->requestSchoolId = $schoolIds->contains((int) session('active_school_id')) ? (int) session('active_school_id') : $schoolIds->first();
    }

    protected function records(): Builder
    {
        if ($this->kind === 'activities') {
            return app(GlobalActivityAccess::class)->available(auth()->user(), true);
        }
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return Announcement::withoutGlobalScope('school')->whereNull('school_id');
    }

    public function save(): void
    {
        $activity = $this->kind === 'activities';
        abort_unless($activity || auth()->user()?->isSuperAdmin(), 403);
        $access = app(GlobalActivityAccess::class);
        if ($activity && ! $this->editingId && ! auth()->user()->isSuperAdmin()) {
            abort_unless($access->schoolAdminIds(auth()->user())->contains($this->requestSchoolId), 403);
        }
        $this->validate([
            'parentActivityId' => ['nullable', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'status' => ['required', $activity ? 'in:draft,published,ongoing,completed,cancelled' : 'in:draft,published'],
            'publishedAt' => ['nullable', 'date'],
            'expiresAt' => ['nullable', 'date', ...($this->publishedAt !== '' ? ['after:publishedAt'] : [])],
            'location' => ['nullable', 'string', 'max:255'],
            'activityType' => ['required', 'in:training,ceremony,camp,competition,service,other'],
            'startsAt' => [$activity ? 'required' : 'nullable', 'date'],
            'endsAt' => [$activity ? 'required' : 'nullable', 'date', 'after:startsAt'],
            'registrationOpen' => ['boolean'],
        ]);
        DB::transaction(function () use ($activity): void {
            $record = $this->editingId ? $this->records()->lockForUpdate()->findOrFail($this->editingId) : ($activity ? new Activity : new Announcement);
            $record->fill([
                'title' => trim($this->title), 'status' => $this->status, 'is_public' => true,
                'published_at' => $this->status === 'draft' ? null : ($this->publishedAt ?: now()),
            ]);
            if ($activity) {
                $record->fill(['description' => $this->body, 'location' => $this->location, 'activity_type' => $this->activityType,
                    'start_at' => $this->startsAt, 'end_at' => $this->endsAt, 'registration_open' => $this->registrationOpen]);
                if (! auth()->user()->isSuperAdmin() && (! $record->exists || $record->approval_status !== 'approved')) {
                    $record->forceFill(['organizer_school_id' => $record->organizer_school_id ?? $this->requestSchoolId,
                        'approval_status' => 'pending', 'status' => 'draft', 'is_public' => false, 'published_at' => null,
                        'reviewed_by' => null, 'reviewed_at' => null, 'rejection_reason' => null]);
                }
            } else {
                $record->fill(['body' => $this->body, 'expires_at' => $this->expiresAt ?: null, 'updated_by' => auth()->id()]);
            }
            if (! $record->exists) {
                $record->created_by = auth()->id();
            }
            $record->school_id = null;
            if ($activity) {
                app(ActivityHierarchyService::class)->assignParent($record, $this->parentActivityId);
            }
            $record->save();
            app(ContentMediaService::class)->save($record, $this->attachmentUploads, $activity ? $this->bannerUpload : null, $this->removeBanner, $this->removeAttachmentIndexes, documentIds: $this->publishedDocumentIds);
        });
        $this->cancelEdit();
        session()->flash('status', 'Informasi umum berhasil disimpan.');
    }

    public function edit(int $id): void
    {
        $record = $this->records()->findOrFail($id);
        $this->editingId = $record->id;
        $this->parentActivityId = $this->kind === 'activities' ? $record->parent_activity_id : null;
        $this->title = $record->title;
        $this->existingAttachments = collect($record->attachments ?? [])->map(fn (array $file): array => ['name' => $file['name']])->all();
        $this->reset('publishedDocumentIds', 'attachmentUploads', 'removeAttachmentIndexes', 'bannerUpload', 'removeBanner');
        $this->body = ($this->kind === 'activities' ? $record->description : $record->body) ?? '';
        $this->status = $record->status;
        $this->publishedAt = $record->published_at?->format('Y-m-d\TH:i') ?? '';
        if ($this->kind === 'activities') {
            $this->location = $record->location ?? '';
            $this->registrationOpen = $record->registration_open;
            $this->activityType = $record->activity_type;
            $this->startsAt = $record->start_at->format('Y-m-d\TH:i');
            $this->endsAt = $record->end_at->format('Y-m-d\TH:i');
        } else {
            $this->expiresAt = $record->expires_at?->format('Y-m-d\TH:i') ?? '';
        }
        $this->resetValidation();
    }

    public function reviewActivity(int $id, bool $approve, GlobalActivityAccess $access): void
    {
        $access->reviewActivity($id, $approve, $this->rejectionReason);
        $this->rejectionReason = '';
        session()->flash('status', $approve ? 'Pengajuan disetujui dan kegiatan diterbitkan.' : 'Pengajuan ditolak.');
    }

    public function cancelEdit(): void
    {
        $this->parentActivityId = null;
        $this->reset('editingId', 'title', 'body', 'status', 'publishedAt', 'expiresAt', 'location', 'activityType', 'startsAt', 'endsAt', 'registrationOpen');
        $this->reset('publishedDocumentIds', 'attachmentUploads', 'existingAttachments', 'removeAttachmentIndexes', 'bannerUpload', 'removeBanner');
        $this->resetValidation();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $parentActivities = $this->kind === 'activities' ? app(GlobalActivityAccess::class)->available(auth()->user())
            ->whereNull('parent_activity_id')->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))->orderBy('title')->get(['id', 'title']) : collect();
        $records = $this->records()->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))->latest('id')->paginate(10);
        $requestSchools = School::whereIn('id', app(GlobalActivityAccess::class)->schoolAdminIds(auth()->user()))->get(['id', 'name']);

        return view('livewire.admin.public-content', compact('records', 'requestSchools', 'parentActivities'));
    }
}
