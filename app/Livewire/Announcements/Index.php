<?php

namespace App\Livewire\Announcements;

use App\Models\Announcement;
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
        $announcements =
            Announcement::query()
                ->with([
                    'creator',
                    'targets',
                ])
                ->when(
                    $this->search,
                    fn ($query) => $query->where(
                        'title',
                        'like',
                        '%'.
                        trim($this->search).
                        '%'
                    )
                )
                ->when(
                    $this->status,
                    fn ($query) => $query->where(
                        'status',
                        $this->status
                    )
                )
                ->latest()
                ->paginate(10);

        return view(
            'livewire.announcements.index',
            compact(
                'announcements'
            )
        );
    }
}
