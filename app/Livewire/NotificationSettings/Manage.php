<?php

namespace App\Livewire\NotificationSettings;

use App\Models\UserNotificationChannel;
use App\Services\TelegramLinkService;
use App\Support\SchoolContext;
use Livewire\Component;

class Manage extends Component
{
    public ?string $telegramLink = null;

    protected function schoolId(): int
    {
        $schoolId =
            app(SchoolContext::class)
                ->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        return $schoolId;
    }

    public function connectTelegram(
        TelegramLinkService $service
    ): void {
        $this->telegramLink =
            $service->createLink(
                auth()->user()
            );
    }

    public function disconnectTelegram(): void
    {
        UserNotificationChannel::query()
            ->where(
                'school_id',
                $this->schoolId()
            )
            ->where(
                'user_id',
                auth()->id()
            )
            ->where(
                'channel',
                'telegram'
            )
            ->update([
                'is_active' => false,
            ]);

        $this->telegramLink = null;

        session()->flash(
            'success',
            'Telegram berhasil dinonaktifkan.'
        );
    }

    public function render()
    {
        $telegramChannel =
            UserNotificationChannel::query()
                ->where(
                    'school_id',
                    $this->schoolId()
                )
                ->where(
                    'user_id',
                    auth()->id()
                )
                ->where(
                    'channel',
                    'telegram'
                )
                ->first();

        return view(
            'livewire.notification-settings.manage',
            compact(
                'telegramChannel'
            )
        );
    }
}
