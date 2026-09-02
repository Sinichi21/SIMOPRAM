<?php

namespace App\Livewire\Announcements;

use App\Models\Announcement;
use App\Models\NotificationLog;
use Livewire\Component;

class MyAnnouncements extends Component
{
    public function render()
    {
        $announcements =
            Announcement::query()
                ->where(
                    'status',
                    'published'
                )
                ->whereNotNull(
                    'published_at'
                )
                ->where(
                    'published_at',
                    '<=',
                    now()
                )
                ->where(
                    function ($query): void {
                        $query
                            ->whereNull(
                                'expires_at'
                            )
                            ->orWhere(
                                'expires_at',
                                '>',
                                now()
                            );
                    }
                )
                ->whereHas(
                    'notificationLogs',
                    fn ($query) =>
                        $query
                            ->where(
                                'user_id',
                                auth()->id()
                            )
                            ->where(
                                'channel',
                                'web'
                            )
                            ->where(
                                'status',
                                'sent'
                            )
                )
                ->latest(
                    'published_at'
                )
                ->get();

        return view(
            'livewire.announcements.my-announcements',
            compact(
                'announcements'
            )
        );
    }
}