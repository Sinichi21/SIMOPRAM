<?php

namespace App\Livewire\CoachAccounts;

use App\Models\Coach;
use App\Services\CoachAccountService;
use Livewire\Component;

class Manage extends Component
{
    public int $coachId;

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(int $coachId): void
    {
        Coach::query()->findOrFail($coachId);
        $this->coachId = $coachId;
    }

    public function createAccount(CoachAccountService $service): void
    {
        abort_unless(auth()->user()->can('coach_accounts.manage'), 403);
        $validated = $this->validate($this->rules());
        $service->createAccount(
            Coach::query()->findOrFail($this->coachId),
            $validated['email'],
            $validated['password']
        );
        $this->resetCredentials();
        session()->flash('success', 'Akun pembina berhasil dibuat.');
    }

    public function resetPassword(CoachAccountService $service): void
    {
        abort_unless(auth()->user()->can('coach_accounts.manage'), 403);
        $validated = $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $service->resetPassword(Coach::query()->findOrFail($this->coachId), $validated['password']);
        $this->resetCredentials();
        session()->flash('success', 'Password pembina berhasil diubah.');
    }

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function resetCredentials(): void
    {
        $this->reset(['email', 'password', 'password_confirmation']);
    }

    public function render()
    {
        $coach = Coach::query()->with('user')->findOrFail($this->coachId);

        return view('livewire.coach-accounts.manage', compact('coach'));
    }
}
