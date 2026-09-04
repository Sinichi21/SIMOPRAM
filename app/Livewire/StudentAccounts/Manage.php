<?php

namespace App\Livewire\StudentAccounts;

use App\Models\Student;
use App\Services\StudentAccountService;
use Livewire\Component;

class Manage extends Component
{
    public int $studentId;

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(
        int $studentId
    ): void {
        Student::query()
            ->findOrFail($studentId);

        $this->studentId =
            $studentId;
    }

    public function createAccount(
        StudentAccountService $service
    ): void {
        abort_unless(
            auth()->user()->can(
                'student_accounts.manage'
            ),
            403
        );

        $validated =
            $this->validate([
                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ]);

        $student =
            Student::query()
                ->findOrFail(
                    $this->studentId
                );

        $service->createAccount(
            $student,
            $validated['email'],
            $validated['password']
        );

        $this->reset([
            'email',
            'password',
            'password_confirmation',
        ]);

        session()->flash(
            'success',
            'Akun siswa berhasil dibuat.'
        );
    }

    public function resetPassword(
        StudentAccountService $service
    ): void {
        abort_unless(
            auth()->user()->can(
                'student_accounts.manage'
            ),
            403
        );

        $validated =
            $this->validate([
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ]);

        $student =
            Student::query()
                ->findOrFail(
                    $this->studentId
                );

        $service->resetPassword(
            $student,
            $validated['password']
        );

        $this->reset([
            'password',
            'password_confirmation',
        ]);

        session()->flash(
            'success',
            'Password siswa berhasil diubah.'
        );
    }

    public function render()
    {
        $student =
            Student::query()
                ->with('user')
                ->findOrFail(
                    $this->studentId
                );

        return view(
            'livewire.student-accounts.manage',
            compact('student')
        );
    }
}
