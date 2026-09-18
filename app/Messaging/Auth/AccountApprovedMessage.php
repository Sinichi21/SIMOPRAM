<?php

namespace App\Messaging\Auth;

use App\Messaging\Contracts\ChannelMessage;
use App\Models\User;

class AccountApprovedMessage implements ChannelMessage
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function subject(): string
    {
        return 'Akun SIMPRAM Anda Telah Disetujui';
    }

    public function emailView(): string
    {
        return 'emails.auth.account-approved';
    }

    public function data(): array
    {
        return [
            'user' => $this->user,
            'loginUrl' => route('login'),
        ];
    }

    public function text(): string
    {
        return implode("\n\n", [
            'Akun SIMPRAM Anda Telah Disetujui',
            "Halo {$this->user->name},",
            'Registrasi akun SIMPRAM Anda telah berhasil diverifikasi dan disetujui.',
            'Akun Anda sekarang sudah dapat digunakan.',
            'Masuk ke SIMPRAM:',
            route('login'),
        ]);
    }
}
