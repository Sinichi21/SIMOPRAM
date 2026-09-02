<?php

namespace App\Livewire\Attendances;

use App\Models\Activity;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $activities = Activity::query()
            ->with([
                'academicYear',
                'semester',
            ])
            ->when(
                $this->search,
                function ($query): void {
                    $search = '%'.trim($this->search).'%';

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('title', 'like', $search)
                            ->orWhere('location', 'like', $search);
                    });
                }
            )
            ->orderByDesc('start_at')
            ->paginate(10);

        return view(
            'livewire.attendances.index',
            compact('activities')
        );
    }
}
