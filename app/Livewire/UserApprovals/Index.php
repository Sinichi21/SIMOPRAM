<?php

namespace App\Livewire\UserApprovals;

use App\Models\User;
use App\Services\UserApprovalService;
use App\Support\SchoolContext;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $role = '';

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

        session()->flash('success', 'Akun berhasil disetujui dan sudah dapat login.');
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
        $schoolId = app(SchoolContext::class)->id();

        $users = User::query()
            ->with('requestedSchool')
            ->where('approval_status', 'pending')
            ->where('requested_school_id', $schoolId)
            ->when($this->role, fn ($query) => $query->where('requested_role', $this->role))
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
