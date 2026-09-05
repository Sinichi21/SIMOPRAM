<?php

namespace App\Livewire\UserApprovals;

use App\Models\User;
use App\Services\AccountActivationService;
use App\Services\UserApprovalService;
use App\Support\SchoolContext;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $role = '';

    public string $status = 'pending';

    #[Locked]
    public ?string $activationLink = null;

    #[Locked]
    public ?int $selectedUserId = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
        $this->reset('activationLink', 'selectedUserId');
    }

    public function sendLink(int $userId, string $channel, AccountActivationService $service): void
    {
        abort_unless(auth()->user()->can('user_approvals.manage'), 403);
        $this->reset('activationLink', 'selectedUserId');
        $this->activationLink = $service->sendLink(User::query()->findOrFail($userId), $channel);
        $this->selectedUserId = $userId;
        session()->flash('success', $channel === 'email' ? 'Tautan telah dikirim melalui email.' : 'Tautan siap dibagikan kepada pemilik akun.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function approve(int $userId, UserApprovalService $service): void
    {
        abort_unless(auth()->user()->can('user_approvals.manage'), 403);

        $service->approve(
            User::query()->findOrFail($userId),
            auth()->user(),
            app(SchoolContext::class)->id()
        );

        $this->status = 'approved';
        $this->resetPage();
        session()->flash('success', 'Akun disetujui. Kirim tautan aktivasi agar pengguna dapat mengatur password dan login.');
    }

    public function reject(int $userId, UserApprovalService $service): void
    {
        abort_unless(auth()->user()->can('user_approvals.manage'), 403);

        $service->reject(
            User::query()->findOrFail($userId),
            auth()->user(),
            app(SchoolContext::class)->id()
        );

        session()->flash('success', 'Pendaftaran ditolak.');
    }

    public function render()
    {
        abort_unless(auth()->user()->can('user_approvals.manage'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409);

        $users = User::query()
            ->with('requestedSchool')
            ->where('approval_status', $this->status === 'approved' ? 'approved' : 'pending')
            ->where(function ($query) use ($schoolId): void {
                if ($this->status === 'approved') {
                    $query->whereHas('schoolMemberships', fn ($membership) => $membership->where('school_id', $schoolId));
                } else {
                    $query->where('requested_school_id', $schoolId);
                }
            })
            ->when($this->role, fn ($query) => $query->where($this->status === 'approved' ? 'system_role' : 'requested_role', $this->role))
            ->when($this->search, function ($query): void {
                $search = '%'.trim($this->search).'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search));
            })
            ->latest()
            ->paginate(10);

        return view('livewire.user-approvals.index', compact('users'));
    }
}
