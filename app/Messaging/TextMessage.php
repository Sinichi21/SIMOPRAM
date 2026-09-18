<?php

namespace App\Messaging;

use App\Messaging\Contracts\ChannelMessage;

class TextMessage implements ChannelMessage
{
    public function __construct(private readonly string $body, private readonly string $title = 'Notifikasi SIMPRAM') {}

    public function subject(): string
    {
        return $this->title;
    }

    public function emailView(): string
    {
        return 'mail.outbound-message';
    }

    /** @return array{messageText: string, messageSubject: string} */
    public function data(): array
    {
        return ['messageText' => $this->body, 'messageSubject' => $this->title];
    }

    public function text(): string
    {
        return $this->body;
    }
}
