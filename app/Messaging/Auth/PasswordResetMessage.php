<?php

namespace App\Messaging\Auth;

use App\Messaging\Contracts\ChannelMessage;
use App\Models\User;

class PasswordResetMessage implements ChannelMessage
{
    public function __construct(
        private readonly User $user,
        private readonly string $resetUrl,
    ) {}

    public function subject(): string
    {
        return 'Reset Password SIMPRAM';
    }

    public function emailView(): string
    {
        return 'emails.auth.password-reset';
    }

    public function data(): array
    {
        return [
            'user' => $this->user,
            'resetUrl' => $this->resetUrl,
        ];
    }

    public function text(): string
    {
        return implode("\n\n", [
            'Reset Password SIMPRAM',
            "Halo {$this->user->name},",
            'Kami menerima permintaan untuk mengatur ulang password akun Anda.',
            "Gunakan tautan berikut:\n{$this->resetUrl}",
            'Jika Anda tidak meminta reset password, abaikan pesan ini.',
        ]);
    }
}
