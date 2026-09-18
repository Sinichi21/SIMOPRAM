<?php

namespace App\Mail\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akun SIMPRAM Anda Telah Disetujui',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.account-approved',
            with: [
                'user' => $this->user,
                'loginUrl' => route('login'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
