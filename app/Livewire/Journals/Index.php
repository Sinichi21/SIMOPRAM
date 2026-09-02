<?php

namespace App\Livewire\Journals;

use App\Models\Activity;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
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
            ])
            ->withCount(
                'attendanceSessions'
            )
            ->when(
                $this->search,
                function ($query): void {
                    $search =
                        '%' .
                        trim($this->search) .
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
                $this->status === 'draft',
                fn ($query) =>
                    $query->whereHas(
                        'journal',
                        fn ($query) =>
                            $query->where(
                                'status',
                                'draft'
                            )
                    )
            )
            ->when(
                $this->status === 'published',
                fn ($query) =>
                    $query->whereHas(
                        'journal',
                        fn ($query) =>
                            $query->where(
                                'status',
                                'published'
                            )
                    )
            )
            ->when(
                $this->status === 'none',
                fn ($query) =>
                    $query->whereDoesntHave(
                        'journal'
                    )
            )
            ->orderByDesc('start_at')
            ->paginate(10);

        return view(
            'livewire.journals.index',
            compact('activities')
        );
    }
}