<?php

namespace App\Services;

use App\Jobs\SendAnnouncementNotification;
use App\Models\Announcement;
use App\Models\NotificationLog;
use App\Support\SchoolContext;
use Illuminate\Support\Collection;

class NotificationService
{
    public function __construct(
        protected AnnouncementAudienceService $audience
    ) {}

    public function publish(
        Announcement $announcement
    ): void {
        if ($announcement->school_id === null) {
            return;
        }

        $users =
            $this->audience->users(
                $announcement
            );

        /*
        |--------------------------------------------------------------------------
        | Web notification
        |--------------------------------------------------------------------------
        */

        $this->sendWeb(
            $announcement,
            $users
        );

        /*
        |--------------------------------------------------------------------------
        | Pesan eksternal via Queue
        |--------------------------------------------------------------------------
        */

        $this->dispatchMessages(
            $announcement,
            $users
        );
    }

    protected function sendWeb(
        Announcement $announcement,
        Collection $users
    ): void {
        foreach ($users as $user) {

            NotificationLog::query()
                ->firstOrCreate(
                    [
                        'announcement_id' => $announcement->id,

                        'user_id' => $user->id,

                        'channel' => 'web',
                    ],
                    [
                        'status' => 'sent',

                        'recipient' => $user->email,

                        'sent_at' => now(),
                    ]
                );
        }
    }

    protected function dispatchMessages(
        Announcement $announcement,
        Collection $users
    ): void {
        $schoolId =
            app(SchoolContext::class)
                ->id();

        abort_unless(
            $schoolId,
            409,
            'SchoolContext tidak tersedia.'
        );

        $channels = array_filter(['telegram', 'whatsapp', 'email'],
            fn (string $channel): bool => app(MessagingService::class)->enabled($channel));
        foreach ($users as $user) {
            foreach ($channels as $channel) {

                $log = NotificationLog::query()
                    ->firstOrCreate(
                        [
                            'announcement_id' => $announcement->id,

                            'user_id' => $user->id,

                            'channel' => $channel,
                        ],
                        [
                            'status' => 'pending',
                        ]
                    );

                if (! $log->wasRecentlyCreated) {
                    continue;
                }
                SendAnnouncementNotification::dispatch(
                    $schoolId,
                    $announcement->id,
                    $user->id,
                    $channel
                )->afterCommit();
            }
        }
    }
}
