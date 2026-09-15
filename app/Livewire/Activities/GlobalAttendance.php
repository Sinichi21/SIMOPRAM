<?php

namespace App\Livewire\Activities;

use App\Services\GlobalActivityAttendance;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class GlobalAttendance extends Component
{
    use WithPagination;

    #[Locked]
    public int $activityId;

    public string $search = '';

    public string $role = '';

    public string $attendance = '';

    public function mount(int $activityId): void
    {
        app(GlobalActivityAttendance::class)->activity($activityId);
        $this->activityId = $activityId;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role', 'attendance'], true)) {
            $this->resetPage();
        }
    }

    public function mark(int $id, string $status, GlobalActivityAttendance $service): void
    {
        $service->mark($this->activityId, $id, $status);
        session()->flash('status', 'Absensi berhasil disimpan.');
    }

    public function render(): View
    {
        $service = app(GlobalActivityAttendance::class);
        $activity = $service->activity($this->activityId);
        $participants = $service->participants($activity, $this->search, $this->role, $this->attendance)->paginate(25);

        return view('livewire.activities.global-attendance', compact('activity', 'participants'));
    }
}
