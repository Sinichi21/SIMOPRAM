<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Services\TelegramService;
use App\Support\SchoolContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendAnnouncementNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;


    public function __construct(
        public int $schoolId,
        public int $announcementId,
        public int $userId,
        public string $channel,
    ) {
    }


    public function handle(
        TelegramService $telegram
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Queue tidak membawa session browser.
        |
        | Jadi SchoolContext harus dipasang manual berdasarkan schoolId job.
        |--------------------------------------------------------------------------
        */

        $school =
            \App\Models\School::query()
                ->withoutGlobalScopes()
                ->findOrFail(
                    $this->schoolId
                );

        app(SchoolContext::class)
            ->set($school);


        $announcement =
            Announcement::query()
                ->findOrFail(
                    $this->announcementId
                );

        $user =
            User::query()
                ->findOrFail(
                    $this->userId
                );


        $log =
            NotificationLog::query()
                ->firstOrCreate(
                    [
                        'announcement_id' =>
                            $announcement->id,

                        'user_id' =>
                            $user->id,

                        'channel' =>
                            $this->channel,
                    ],
                    [
                        'status' =>
                            'pending',
                    ]
                );


        try {

            match ($this->channel) {

                'telegram' =>
                    $this->sendTelegram(
                        $telegram,
                        $announcement,
                        $user,
                        $log
                    ),

                default =>
                    throw new \RuntimeException(
                        "Channel [{$this->channel}] tidak didukung."
                    ),
            };

        } catch (Throwable $exception) {

            $log->update([
                'status' =>
                    'failed',

                'error_message' =>
                    mb_substr(
                        $exception->getMessage(),
                        0,
                        5000
                    ),
            ]);

            throw $exception;
        }
    }


    protected function sendTelegram(
        TelegramService $telegram,
        Announcement $announcement,
        User $user,
        NotificationLog $log
    ): void {
        $channel =
            UserNotificationChannel::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'channel',
                    'telegram'
                )
                ->where(
                    'is_verified',
                    true
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();


        if (! $channel) {
            /*
            |--------------------------------------------------------------------------
            | Bukan error teknis.
            | User memang belum menghubungkan Telegram.
            |--------------------------------------------------------------------------
            */

            $log->update([
                'status' =>
                    'failed',

                'error_message' =>
                    'Akun Telegram pengguna belum terhubung.',
            ]);

            return;
        }


        $message =
            $this->formatTelegramMessage(
                $announcement
            );


        $response =
            $telegram->sendMessage(
                $channel->destination,
                $message
            );


        $telegramMessageId =
            data_get(
                $response,
                'result.message_id'
            );


        $log->update([
            'status' =>
                'sent',

            'recipient' =>
                $channel->destination,

            'response' =>
                $telegramMessageId
                    ? 'message_id='
                        . $telegramMessageId
                    : 'sent',

            'error_message' =>
                null,

            'sent_at' =>
                now(),
        ]);
    }


    protected function formatTelegramMessage(
        Announcement $announcement
    ): string {
        $schoolName =
            $announcement
                ->school
                ?->name
            ?? 'SIMOPRAM';

        return implode(
            PHP_EOL,
            [
                '📢 PENGUMUMAN PRAMUKA',
                '',
                $schoolName,
                '',
                $announcement->title,
                '',
                $announcement->body,
            ]
        );
    }
}