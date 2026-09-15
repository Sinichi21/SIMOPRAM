<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Models\User;
use App\Services\GlobalActivityAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GlobalDelegates extends Component
{
    #[Locked]
    public int $activityId;

    public string $search = '';

    public ?int $userId = null;

    protected function activity(): Activity
    {
        $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->findOrFail($this->activityId);
        app(GlobalActivityAccess::class)->authorize($activity);

        return $activity;
    }

    public function mount(int $activityId): void
    {
        $this->activityId = $activityId;
        $this->activity();
    }

    public function assign(GlobalActivityAccess $access): void
    {
        $this->validate(['userId' => ['required', 'integer']]);
        $grant = $access->delegate($this->activity(), $this->userId);
        session()->flash('status', $grant->status === 'approved' ? 'Delegasi aktif untuk kegiatan ini.' : 'Delegasi menunggu persetujuan super admin.');
        $this->userId = null;
    }

    public function review(int $id, bool $approve, GlobalActivityAccess $access): void
    {
        $this->activity()->delegates()->findOrFail($id);
        $access->reviewDelegate($id, $approve);
    }

    public function revoke(int $id, GlobalActivityAccess $access): void
    {
        $access->revoke($this->activity(), $id);
    }

    public function render(): View
    {
        $activity = $this->activity();
        $canDelegate = app(GlobalActivityAccess::class)->canDelegate(auth()->user(), $activity);
        $users = $canDelegate && mb_strlen(trim($this->search)) >= 2 ? User::where('is_active', true)
            ->where(fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%')->orWhere('email', trim($this->search)))
            ->select('id', 'name')->orderBy('name')->limit(30)->get() : collect();
        $delegates = $activity->delegates()->with('user:id,name')->latest('id')->get();

        return view('livewire.activities.global-delegates', compact('activity', 'canDelegate', 'users', 'delegates'));
    }
}
