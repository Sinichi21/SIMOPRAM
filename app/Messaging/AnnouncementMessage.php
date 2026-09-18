<?php

namespace App\Messaging;

use App\Models\Announcement;

class AnnouncementMessage extends TextMessage
{
    public function __construct(private readonly Announcement $announcement, private readonly string $schoolName, bool $telegram = false)
    {
        $body = strip_tags($announcement->body);
        parent::__construct($schoolName."\n\n".$announcement->title."\n\n".($telegram ? mb_substr($body, 0, 3000) : $body)."\n\n".route('announcements.my'), $announcement->title);
    }

    public function emailView(): string
    {
        return 'mail.notification.announcement';
    }

    /** @return array{title: string, schoolName: string, body: string, url: string} */
    public function data(): array
    {
        return ['title' => $this->subject(), 'schoolName' => $this->schoolName,
            'body' => strip_tags($this->announcement->body), 'url' => route('announcements.my')];
    }
}
