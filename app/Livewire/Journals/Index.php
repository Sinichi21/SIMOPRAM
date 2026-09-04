<?php

namespace App\Livewire\Journals;

use App\Models\Activity;
use App\Models\ScoutLevel;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $scoutLevelId = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedScoutLevelId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $activities = Activity::query()
            ->with([
                'academicYear',
                'semester',
                'coaches',
                'journal',
                'scoutLevels',
            ])
            ->withCount(
                'attendanceSessions'
            )
            ->when(
                $this->search,
                function ($query): void {
                    $search =
                        '%'.
                        trim($this->search).
                        '%';

                    $query->where(
                        function ($query) use ($search): void {
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
                $this->scoutLevelId,
                function ($query): void {
                    $query->where(
                        function ($query): void {
                            $query
                                ->whereDoesntHave('scoutLevels')
                                ->orWhereHas(
                                    'scoutLevels',
                                    fn ($query) => $query->whereKey(
                                        (int) $this->scoutLevelId
                                    )
                                );
                        }
                    );
                }
            )
            ->when(
                $this->status === 'draft',
                fn ($query) => $query->whereHas(
                    'journal',
                    fn ($query) => $query->where(
                        'status',
                        'draft'
                    )
                )
            )
            ->when(
                $this->status === 'published',
                fn ($query) => $query->whereHas(
                    'journal',
                    fn ($query) => $query->where(
                        'status',
                        'published'
                    )
                )
            )
            ->when(
                $this->status === 'none',
                fn ($query) => $query->whereDoesntHave(
                    'journal'
                )
            )
            ->orderByDesc('start_at')
            ->paginate(10);

        $scoutLevels = ScoutLevel::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'livewire.journals.index',
            compact('activities', 'scoutLevels')
        );
    }
}
