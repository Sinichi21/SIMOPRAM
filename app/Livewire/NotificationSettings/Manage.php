<?php

namespace App\Livewire\NotificationSettings;

use App\Models\UserNotificationChannel;
use App\Services\MessagingService;
use App\Services\TelegramLinkService;
use App\Support\SchoolContext;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Manage extends Component
{
    public ?string $telegramLink = null;

    public string $whatsappNumber = '';

    public bool $whatsappEnabled = false;

    public bool $emailEnabled = false;

    public function mount(): void
    {
        $this->schoolId();
        $channels = UserNotificationChannel::query()->where('user_id', auth()->id())->get()->keyBy('channel');
        $this->whatsappNumber = $channels->get('whatsapp')?->destination ?? auth()->user()->phone ?? '';
        $this->whatsappEnabled = $channels->get('whatsapp')?->is_active ?? false;
        $this->emailEnabled = $channels->get('email')?->is_active ?? false;
    }

    public function savePreferences(MessagingService $messaging): void
    {
        $this->schoolId();
        abort_unless(auth()->check(), 403);
        $this->validate(['whatsappEnabled' => ['boolean'], 'emailEnabled' => ['boolean'],
            'whatsappNumber' => ['nullable', 'required_if:whatsappEnabled,true', 'string', 'max:30']]);
        if ($this->whatsappEnabled) {
            try {
                $this->whatsappNumber = $messaging->destination('whatsapp', $this->whatsappNumber);
            } catch (ValidationException) {
                throw ValidationException::withMessages(['whatsappNumber' => 'Masukkan satu nomor WhatsApp yang valid.']);
            }
        }
        UserNotificationChannel::query()->updateOrCreate(['user_id' => auth()->id(), 'channel' => 'whatsapp'], [
            'destination' => $this->whatsappNumber, 'is_active' => $this->whatsappEnabled,
            'is_verified' => false, 'verified_at' => null,
        ]);
        UserNotificationChannel::query()->updateOrCreate(['user_id' => auth()->id(), 'channel' => 'email'], [
            'destination' => auth()->user()->email, 'is_active' => $this->emailEnabled,
            'is_verified' => auth()->user()->email_verified_at !== null,
        ]);
        session()->flash('success', 'Pilihan notifikasi berhasil disimpan.');
    }

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
