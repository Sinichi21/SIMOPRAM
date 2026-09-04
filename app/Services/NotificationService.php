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
        | Telegram via Queue
        |--------------------------------------------------------------------------
        */

        $this->dispatchTelegram(
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

    protected function dispatchTelegram(
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

        foreach ($users as $user) {

            NotificationLog::query()
                ->firstOrCreate(
                    [
                        'announcement_id' => $announcement->id,

                        'user_id' => $user->id,

                        'channel' => 'telegram',
                    ],
                    [
                        'status' => 'pending',
                    ]
                );

            SendAnnouncementNotification::dispatch(
                $schoolId,
                $announcement->id,
                $user->id,
                'telegram'
            );
        }
    }
}
