<?php

namespace App\Livewire\AccountDirectory;

use App\Models\Coach;
use App\Models\Student;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Locked]
    public string $type;

    public string $search = '';

    public function mount(string $type): void
    {
        abort_unless(in_array($type, ['coach', 'student'], true), 404);
        $this->type = $type;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless(auth()->user()->can($this->type.'_accounts.manage'), 403);
        abort_unless(app(SchoolContext::class)->id(), 409);
        $profiles = ($this->type === 'coach' ? Coach::query() : Student::query())
            ->with('user')
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', '%'.trim($this->search).'%')
                    ->orWhereHas('user', fn ($user) => $user->where('email', 'like', '%'.trim($this->search).'%'));
            }))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.account-directory.index', compact('profiles'));
    }
}
