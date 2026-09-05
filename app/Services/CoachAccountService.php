<?php

namespace App\Services;

use App\Models\Coach;
use App\Models\User;

class CoachAccountService
{
    public function createAccount(Coach $coach, string $email): User
    {
        return app(AccountActivationService::class)->linkAccount($coach, $email);
    }
}
