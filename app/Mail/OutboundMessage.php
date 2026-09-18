<?php

namespace App\Mail;

use App\Messaging\Contracts\ChannelMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OutboundMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly ChannelMessage $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->message->subject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->message->emailView(),
            with: $this->message->data(),
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
