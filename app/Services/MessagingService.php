<?php

namespace App\Services;

use App\Mail\OutboundMessage;
use App\Models\MessagingSetting;
use App\Models\UserNotificationChannel;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class MessagingService
{
    public function enabled(string $channel): bool
    {
        $setting = MessagingSetting::forChannel($channel);

        return $setting ? $setting->enabled : match ($channel) {
            'telegram' => filled(config('services.telegram.bot_token')),
            'email' => ! in_array(config('mail.default'), ['log', 'array'], true),
            default => false,
        };
    }

    public function destination(string $channel, string $destination): string
    {
        $destination = trim($destination);
        if ($channel === 'whatsapp') {
            Validator::make(['destination' => $destination], ['destination' => ['required', 'regex:/^\+?[0-9][0-9 ()-]{7,24}$/']])->validate();
            $destination = preg_replace('/[ ()+-]/', '', $destination);
            if (str_starts_with($destination, '0')) {
                $destination = '62'.substr($destination, 1);
            }
            Validator::make(['destination' => $destination], ['destination' => ['regex:/^[1-9][0-9]{7,14}$/']])->validate();
        } elseif ($channel === 'email') {
            Validator::make(['destination' => $destination], ['destination' => ['required', 'email', 'max:255']])->validate();
        } elseif ($channel === 'telegram') {
            if (! preg_match('/^-?[1-9][0-9]{0,19}$/', $destination)) {
                abort_unless(app(SchoolContext::class)->hasSchool(), 409);
                $username = ltrim($destination, '@');
                Validator::make(['destination' => $username], ['destination' => ['regex:/^[a-zA-Z0-9_]{5,32}$/']])->validate();
                $usernameExpression = DB::connection()->getDriverName() === 'sqlite'
                    ? "LOWER(json_extract(metadata, '$.username')) = ?"
                    : "LOWER(json_unquote(json_extract(metadata, '$.username'))) = ?";
                $destination = UserNotificationChannel::query()->where('channel', 'telegram')
                    ->where('is_active', true)->where('is_verified', true)
                    ->whereRaw($usernameExpression, [mb_strtolower($username)])
                    ->value('destination') ?? '';
                if ($destination === '') {
                    throw new RuntimeException('Username Telegram belum terhubung di sekolah ini. Penerima perlu menghubungkan Telegram melalui Pengaturan Notifikasi sekali.');
                }
            }
        } else {
            throw new RuntimeException('Saluran pengiriman tidak valid.');
        }

        return $destination;
    }

    public function send(string $channel, string $destination, string $message, string $subject = 'Notifikasi SIMPRAM'): string
    {
        if (! $this->enabled($channel)) {
            throw new RuntimeException('Saluran belum aktif. Hubungi Super Admin untuk konfigurasi pengiriman.');
        }
        $destination = $this->destination($channel, $destination);
        try {
            if ($channel === 'telegram') {
                $response = app(TelegramService::class)->sendMessage($destination, $message);

                return 'message_id='.data_get($response, 'result.message_id');
            }
            if ($channel === 'whatsapp') {
                $token = MessagingSetting::forChannel('whatsapp')?->options['token'] ?? '';
                if (! $token) {
                    throw new RuntimeException('Token Fonnte belum dikonfigurasi.');
                }
                $response = Http::connectTimeout(5)->timeout(15)->asForm()
                    ->withHeaders(['Authorization' => $token])->post('https://api.fonnte.com/send', [
                        'target' => $destination, 'message' => $message, 'countryCode' => '0', 'connectOnly' => true,
                    ]);
                if (! $response->successful() || $response->json('status') !== true) {
                    throw new RuntimeException('Fonnte menolak pesan.');
                }

                return 'accepted';
            }
            $this->configureMail();
            Mail::to($destination)->send(new OutboundMessage($message, $subject));

            return 'accepted';
        } catch (Throwable) {
            throw new RuntimeException('Pengiriman belum terkonfirmasi. Periksa konfigurasi, koneksi perangkat, kuota, dan riwayat penyedia sebelum mengirim ulang.');
        }
    }

    public function configureMail(): void
    {
        $setting = MessagingSetting::forChannel('email');
        if (! $setting) {
            return;
        }
        if (! $setting->enabled) {
            throw new RuntimeException('Pengiriman email dinonaktifkan oleh Super Admin.');
        }
        $options = $setting->options;
        if (empty($options['host']) || empty($options['port']) || empty($options['from_address'])) {
            throw new RuntimeException('Konfigurasi SMTP belum lengkap.');
        }
        config(['mail.default' => 'messaging', 'mail.mailers.messaging' => [
            'transport' => 'smtp', 'host' => $options['host'], 'port' => (int) $options['port'],
            'scheme' => $options['scheme'] ?? 'smtp', 'username' => ($options['username'] ?? '') ?: null,
            'password' => $options['password'] ?? null, 'timeout' => 15,
        ], 'mail.from' => ['address' => $options['from_address'], 'name' => $options['from_name'] ?? 'SIMPRAM']]);
        Mail::purge('messaging');
    }
}
