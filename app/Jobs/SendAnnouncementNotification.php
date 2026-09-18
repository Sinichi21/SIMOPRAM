<?php

namespace App\Jobs;

use App\Messaging\AnnouncementMessage;
use App\Models\Announcement;
use App\Models\NotificationLog;
use App\Models\School;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Services\MessagingService;
use App\Support\SchoolContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SendAnnouncementNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public int $schoolId,
        public int $announcementId,
        public int $userId,
        public string $channel,
    ) {}

    public function handle(MessagingService $messaging): void
    {
        $context = app(SchoolContext::class);
        $previous = $context->school();
        $lock = Cache::lock("announcement-message:{$this->schoolId}:{$this->announcementId}:{$this->userId}:{$this->channel}", 60);
        if (! $lock->get()) {
            return;
        }
        try {
            $context->set(School::query()->findOrFail($this->schoolId));
            $announcement = Announcement::query()->findOrFail($this->announcementId);
            $user = User::query()->findOrFail($this->userId);
            $log = NotificationLog::query()->firstOrCreate([
                'announcement_id' => $this->announcementId, 'user_id' => $this->userId, 'channel' => $this->channel,
            ], ['status' => 'pending']);
            if (in_array($log->status, ['sent', 'accepted', 'processing', 'failed'], true)) {
                return;
            }
            $recipient = UserNotificationChannel::query()->where('user_id', $user->id)
                ->where('channel', $this->channel)->where('is_active', true)
                ->when($this->channel === 'telegram', fn ($query) => $query->where('is_verified', true))->first();
            if (! $user->is_active || ! $recipient || ! $messaging->enabled($this->channel)) {
                $log->update(['status' => 'skipped', 'error_message' => 'Saluran tidak aktif atau penerima belum terhubung.']);

                return;
            }
            $destination = $this->channel === 'email' ? $user->email : $recipient->destination;
            $log->update(['status' => 'processing', 'recipient' => $destination]);
            try {
                $response = $messaging->sendMessage($this->channel, $destination,
                    new AnnouncementMessage($announcement, $context->school()->name, $this->channel === 'telegram'));
                $log->update(['status' => 'sent', 'response' => $response, 'sent_at' => now(), 'error_message' => null]);
            } catch (Throwable) {
                $log->update(['status' => 'failed', 'error_message' => 'Pengiriman belum terkonfirmasi. Periksa konfigurasi dan riwayat penyedia sebelum mengirim ulang.']);
            }
        } finally {
            $previous ? $context->set($previous) : $context->clear();
            $lock->release();
        }
    }
}
