<?php

namespace App\Livewire\ActivityLogs;

use App\Models\ActivityLog;
use App\Services\ActivityLogQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /** @var array<string, string> */
    public array $filters = [];

    #[Locked]
    public ?int $selectedId = null;

    public bool $showDetail = false;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function mount(): void
    {
        $this->filters = [
            'from' => now(config('activity-log.timezone'))->subDays(6)->toDateString(),
            'to' => now(config('activity-log.timezone'))->toDateString(),
            'school' => '', 'user' => '', 'role' => '', 'module' => '',
            'action' => '', 'status' => '', 'type' => '', 'request_id' => '',
        ];
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function show(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        ActivityLog::query()->findOrFail($id);
        $this->selectedId = $id;
        $this->showDetail = true;
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        try {
            $query = app(ActivityLogQuery::class)->build($this->filters);
            $this->resetValidation();
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->errors());
            $query = ActivityLog::query()->whereRaw('1 = 0');
        }

        return view('livewire.activity-logs.index', [
            'logs' => $query->paginate(25),
            'schools' => ActivityLog::query()->whereNotNull('school_id')->select('school_id', 'school_name')->distinct()->orderBy('school_name')->get(),
            'selected' => $this->showDetail && $this->selectedId ? ActivityLog::query()->findOrFail($this->selectedId) : null,
        ]);
    }
}
