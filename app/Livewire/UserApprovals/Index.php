<?php

namespace App\Livewire\UserApprovals;

use App\Models\User;
use App\Services\AccountActivationService;
use App\Services\PrincipalAccountService;
use App\Services\UserApprovalService;
use App\Support\SchoolContext;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    #[On('users-changed')]
    public function refreshUsers(): void
    {
        $this->resetPage();
    }

    public string $principalName = '';

    public string $principalEmail = '';

    public function createPrincipal(PrincipalAccountService $service): void
    {
        $service->create($this->principalName, $this->principalEmail);
        $this->reset('principalName', 'principalEmail', 'activationLink', 'selectedUserId');
        $this->status = 'approved';
        $this->role = 'principal';
        $this->resetPage();
        session()->flash('success', 'Akun Kepala Sekolah dibuat. Bagikan tautan aktivasi agar pengguna dapat membuat password dan login.');
    }

    public string $activationDestination = '';

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
        $this->activationLink = $service->sendLink(User::query()->findOrFail($userId), $channel, $this->activationDestination);
        $this->selectedUserId = $userId;
        session()->flash('success', $channel === 'share' ? 'Tautan siap disalin.' : 'Tautan diterima layanan pengiriman.');
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
        session()->flash('success', 'Pendaftaran disetujui. Akun sudah aktif dan pengguna dapat login menggunakan password yang dibuat saat registrasi.');
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
            ->with(['requestedSchool', 'schoolMemberships' => fn ($query) => $query->where('school_id', $schoolId)])
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
