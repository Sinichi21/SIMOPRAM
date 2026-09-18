<?php

namespace App\Livewire\Activities;

use App\Jobs\SendActivityAccessLink;
use App\Models\Activity;
use App\Models\ActivityMessageDelivery;
use App\Services\ActivityEntryService;
use App\Services\ActivityNotificationService;
use App\Services\ActivityRegistrationService;
use App\Services\GlobalActivityAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class GlobalParticipants extends Component
{
    use WithPagination;

    #[Locked]
    public int $activityId;

    #[Locked]
    public ?int $selectedId = null;

    public string $search = '';

    public string $category = '';

    public string $status = '';

    public string $validation = '';

    public string $order = 'newest';

    public string $notificationTitle = '';

    public string $notificationBody = '';

    public function sendAnnouncement(ActivityNotificationService $notifications): void
    {
        $activity = $this->activity();
        $this->validate([
            'notificationTitle' => ['required', 'string', 'max:150'],
            'notificationBody' => ['required', 'string', 'max:5000'],
        ], attributes: ['notificationTitle' => 'judul pengumuman', 'notificationBody' => 'isi pengumuman']);
        $count = $notifications->announce($activity, $this->notificationTitle, $this->notificationBody);
        $this->reset('notificationTitle', 'notificationBody');
        session()->flash('status', $count.' pesan dijadwalkan untuk peserta aktif kegiatan ini.');
    }

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

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'status', 'validation', 'order'], true)) {
            $this->resetPage();
        }
    }

    public function detail(int $id): void
    {
        $this->activity()->entries()->findOrFail($id);
        $this->selectedId = $id;
    }

    public function validateEntry(int $id): void
    {
        $activity = $this->activity();
        DB::transaction(function () use ($id, $activity): void {
            $entry = $activity->entries()->lockForUpdate()->findOrFail($id);
            abort_unless($entry->status === 'active', 409);
            [$min, $max] = app(ActivityEntryService::class)->limits($activity, $entry->category);
            $members = $entry->members()->where('status', 'active')->get();
            $count = $members->where('role', 'student')->where('is_reserve', false)->count();
            abort_unless($count >= $min && $count <= $max && $members->where('role', 'coach')->count() === 1 && $members->where('is_reserve', true)->count() <= 1, 422, 'Komposisi peserta tidak sesuai kategori.');
            $entry->update(['validation_status' => 'validated', 'validated_at' => now(), 'validated_by' => auth()->id()]);
        });
        session()->flash('status', 'Peserta berhasil divalidasi.');
    }

    public function setActive(int $id, bool $active): void
    {
        $activity = $this->activity();
        DB::transaction(function () use ($activity, $id, $active): void {
            $entry = $activity->entries()->lockForUpdate()->findOrFail($id);
            if ($entry->status === ($active ? 'active' : 'inactive')) {
                return;
            }
            $entry->update(['status' => $active ? 'active' : 'inactive']);
            foreach ($entry->members()->where('status', 'active')->get() as $member) {
                $member->forceFill(['token_hash' => null, 'access_version' => $member->access_version + 1,
                    'delivery_status' => $active ? 'pending' : 'revoked', 'link_requested_at' => now()])->save();
                if ($active) {
                    SendActivityAccessLink::dispatch($member->id, $member->access_version)->afterCommit();
                }
            }
        });
        session()->flash('status', $active ? 'Peserta diaktifkan. Akses baru dijadwalkan untuk dikirim.' : 'Peserta dinonaktifkan dan seluruh akses dicabut.');
    }

    public function resend(int $id, ActivityRegistrationService $service): void
    {
        $entry = $this->activity()->entries()->findOrFail($id);
        abort_unless($entry->status === 'active', 403);
        DB::transaction(function () use ($entry, $service): void {
            foreach ($entry->members()->where('status', 'active')->get() as $member) {
                $service->resend($member);
            }
        });
        session()->flash('status', 'Pengiriman ulang dijadwalkan. Link lama tidak berlaku.');
    }

    public function render(): View
    {
        $activity = $this->activity();
        $entries = $activity->entries()->withCount(['members' => fn ($query) => $query->where('status', 'active')])
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$this->search.'%')
                ->orWhereHas('members', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%')->orWhere('identifier', 'like', '%'.$this->search.'%')->orWhere('school_name', 'like', '%'.$this->search.'%'))))
            ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->validation !== '', fn ($query) => $query->where('validation_status', $this->validation));
        match ($this->order) {
            'oldest' => $entries->orderBy('id'), 'name' => $entries->orderBy('name')->orderBy('id'),
            'name_desc' => $entries->orderByDesc('name')->orderBy('id'), default => $entries->orderByDesc('id'),
        };
        $entries = $entries->paginate(15);
        $selected = $this->selectedId ? $activity->entries()->with(['members' => fn ($query) => $query->where('status', 'active')])->findOrFail($this->selectedId) : null;

        $deliveries = ActivityMessageDelivery::query()->whereHas('registration', fn ($query) => $query->where('activity_id', $activity->id))
            ->with('registration:id,name')->latest('id')->limit(10)->get();

        return view('livewire.activities.global-participants', compact('activity', 'entries', 'selected', 'deliveries'));
    }
}
