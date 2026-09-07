<?php

namespace App\Livewire\Messaging;

use App\Models\MessagingSetting;
use App\Services\MessagingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class Settings extends Component
{
    public bool $whatsappEnabled = false;

    public bool $telegramEnabled = false;

    public bool $emailEnabled = false;

    public string $fonnteToken = '';

    public string $telegramToken = '';

    public string $telegramUsername = '';

    public string $host = '';

    public string $port = '587';

    public string $scheme = 'smtp';

    public string $username = '';

    public string $password = '';

    public string $fromAddress = '';

    public string $fromName = 'SIMPRAM';

    public string $testChannel = 'whatsapp';

    public string $testDestination = '';

    public function mount(): void
    {
        $this->authorizeSettings();
        $whatsapp = MessagingSetting::forChannel('whatsapp');
        $telegram = MessagingSetting::forChannel('telegram');
        $email = MessagingSetting::forChannel('email');
        $this->whatsappEnabled = $whatsapp->enabled ?? false;
        $this->telegramEnabled = $telegram->enabled ?? filled(config('services.telegram.bot_token'));
        $this->emailEnabled = $email->enabled ?? false;
        $this->telegramUsername = $telegram?->options['username'] ?? config('services.telegram.bot_username', '') ?? '';
        $options = $email->options ?? [];
        foreach (['host', 'port', 'scheme', 'username'] as $field) {
            $this->{$field} = (string) ($options[$field] ?? $this->{$field});
        }
        $this->fromAddress = $options['from_address'] ?? '';
        $this->fromName = $options['from_name'] ?? 'SIMPRAM';
    }

    protected function authorizeSettings(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function save(): void
    {
        $this->authorizeSettings();
        $this->validate([
            'whatsappEnabled' => ['boolean'], 'telegramEnabled' => ['boolean'], 'emailEnabled' => ['boolean'],
            'fonnteToken' => ['nullable', 'string', 'max:255', 'regex:/^[^\r\n,]*$/'],
            'telegramToken' => ['nullable', 'regex:/^[0-9]+:[a-zA-Z0-9_-]+$/', 'max:255'],
            'telegramUsername' => ['nullable', 'required_if:telegramEnabled,true', 'regex:/^[a-zA-Z0-9_]{5,32}$/'],
            'host' => ['nullable', 'required_if:emailEnabled,true', 'regex:/^[a-zA-Z0-9.-]+$/', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'scheme' => ['required', 'in:smtp,smtps'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1000'],
            'fromAddress' => ['nullable', 'required_if:emailEnabled,true', 'email', 'max:255'],
            'fromName' => ['required', 'string', 'max:255'],
        ]);
        $fonnteToken = $this->fonnteToken ?: (MessagingSetting::forChannel('whatsapp')?->options['token'] ?? '');
        $telegramToken = $this->telegramToken ?: (MessagingSetting::forChannel('telegram')?->options['token'] ?? config('services.telegram.bot_token'));
        if ($this->whatsappEnabled && ! $fonnteToken) {
            throw ValidationException::withMessages(['fonnteToken' => 'Masukkan token perangkat Fonnte.']);
        }
        if ($this->telegramEnabled && ! $telegramToken) {
            throw ValidationException::withMessages(['telegramToken' => 'Masukkan token bot Telegram.']);
        }
        $password = $this->password ?: (MessagingSetting::forChannel('email')?->options['password'] ?? '');
        DB::transaction(function () use ($fonnteToken, $telegramToken, $password): void {
            MessagingSetting::query()->updateOrCreate(['channel' => 'whatsapp'], [
                'enabled' => $this->whatsappEnabled, 'options' => ['token' => $fonnteToken],
            ]);
            MessagingSetting::query()->updateOrCreate(['channel' => 'telegram'], [
                'enabled' => $this->telegramEnabled, 'options' => ['token' => $telegramToken, 'username' => $this->telegramUsername],
            ]);
            MessagingSetting::query()->updateOrCreate(['channel' => 'email'], [
                'enabled' => $this->emailEnabled, 'options' => [
                    'host' => $this->host, 'port' => $this->port, 'scheme' => $this->scheme,
                    'username' => $this->username, 'password' => $password,
                    'from_address' => $this->fromAddress, 'from_name' => $this->fromName,
                ],
            ]);
        });
        $this->reset('fonnteToken', 'telegramToken', 'password');
        session()->flash('success', 'Konfigurasi pengiriman berhasil disimpan.');
    }

    public function sendTest(MessagingService $messaging): void
    {
        $this->authorizeSettings();
        $this->validate(['testChannel' => ['required', 'in:whatsapp,telegram,email'], 'testDestination' => ['required', 'string', 'max:255']]);
        $key = 'messaging-test:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['testDestination' => 'Tunggu satu menit sebelum uji kirim lagi.']);
        }
        RateLimiter::hit($key, 60);
        try {
            $messaging->send($this->testChannel, $this->testDestination, 'Uji pengiriman SIMPRAM berhasil. Pesan dikirim langsung dari server.');
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['testDestination' => $exception instanceof \RuntimeException ? $exception->getMessage() : 'Tujuan tidak valid atau pengiriman belum terkonfirmasi.']);
        }
        session()->flash('success', 'Pesan uji diterima layanan pengiriman. Periksa pesan pada penerima.');
    }

    public function render(): View
    {
        $this->authorizeSettings();

        return view('livewire.messaging.settings');
    }
}
