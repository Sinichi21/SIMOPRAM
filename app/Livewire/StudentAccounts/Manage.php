<?php

namespace App\Livewire\StudentAccounts;

use App\Models\Student;
use App\Services\AccountActivationService;
use App\Services\StudentAccountService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Manage extends Component
{
    #[Locked]
    public int $studentId;

    public string $email = '';

    #[Locked]
    public ?string $activationLink = null;

    public function mount(int $studentId): void
    {
        abort_unless(auth()->user()->can('student_accounts.manage'), 403);
        Student::query()->findOrFail($studentId);
        $this->studentId = $studentId;
    }

    public function createAccount(StudentAccountService $service): void
    {
        abort_unless(auth()->user()->can('student_accounts.manage'), 403);
        $validated = $this->validate(['email' => ['required', 'email', 'max:255']]);
        $service->createAccount(Student::query()->findOrFail($this->studentId), $validated['email']);
        $this->reset('email', 'activationLink');
        session()->flash('success', 'Akun berhasil dihubungkan. Untuk akun baru, kirim tautan aktivasi di bawah.');
    }

    public function sendLink(string $channel, AccountActivationService $service): void
    {
        abort_unless(auth()->user()->can('student_accounts.manage'), 403);
        $this->activationLink = null;
        $this->activationLink = $service->sendLink(
            Student::query()->findOrFail($this->studentId)->user()->firstOrFail(), $channel
        );
        session()->flash('success', $channel === 'email' ? 'Tautan pengaturan password telah dikirim melalui email.' : 'Tautan siap dibagikan kepada pemilik akun.');
    }

    public function render(): View
    {
        abort_unless(auth()->user()->can('student_accounts.manage'), 403);
        $student = Student::query()->with('user')->findOrFail($this->studentId);

        return view('livewire.student-accounts.manage', compact('student'));
    }
}
