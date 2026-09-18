<?php

namespace App\Livewire\Announcements;

use App\Services\AnnouncementAudienceService;
use Livewire\Component;

class MyAnnouncements extends Component
{
    public function render()
    {
        $user =
            auth()->user();

        $announcements =
            app(
                AnnouncementAudienceService::class
            )
                ->publishedForUser(
                    $user
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
