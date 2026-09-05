<?php

namespace App\Livewire\CoachAccounts;

use App\Models\Coach;
use App\Services\AccountActivationService;
use App\Services\CoachAccountService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Manage extends Component
{
    #[Locked]
    public int $coachId;

    public string $email = '';

    #[Locked]
    public ?string $activationLink = null;

    public function mount(int $coachId): void
    {
        abort_unless(auth()->user()->can('coach_accounts.manage'), 403);
        Coach::query()->findOrFail($coachId);
        $this->coachId = $coachId;
    }

    public function createAccount(CoachAccountService $service): void
    {
        abort_unless(auth()->user()->can('coach_accounts.manage'), 403);
        $validated = $this->validate(['email' => ['required', 'email', 'max:255']]);
        $service->createAccount(Coach::query()->findOrFail($this->coachId), $validated['email']);
        $this->reset('email', 'activationLink');
        session()->flash('success', 'Akun berhasil dihubungkan. Untuk akun baru, kirim tautan aktivasi di bawah.');
    }

    public function sendLink(string $channel, AccountActivationService $service): void
    {
        abort_unless(auth()->user()->can('coach_accounts.manage'), 403);
        $this->activationLink = null;
        $this->activationLink = $service->sendLink(
            Coach::query()->findOrFail($this->coachId)->user()->firstOrFail(), $channel
        );
        session()->flash('success', $channel === 'email' ? 'Tautan pengaturan password telah dikirim melalui email.' : 'Tautan siap dibagikan kepada pemilik akun.');
    }

    public function render(): View
    {
        abort_unless(auth()->user()->can('coach_accounts.manage'), 403);
        $coach = Coach::query()->with('user')->findOrFail($this->coachId);

        return view('livewire.coach-accounts.manage', compact('coach'));
    }
}
