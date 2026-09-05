<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;

class StudentAccountService
{
    public function createAccount(Student $student, string $email): User
    {
        return app(AccountActivationService::class)->linkAccount($student, $email);
    }
}
