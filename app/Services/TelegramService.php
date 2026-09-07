<?php

namespace App\Services;

use App\Models\MessagingSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramService
{
    protected function token(): string
    {
        $setting = MessagingSetting::forChannel('telegram');
        if ($setting && ! $setting->enabled) {
            throw new RuntimeException('Pengiriman Telegram dinonaktifkan.');
        }
        $token = $setting?->options['token'] ?? config(
            'services.telegram.bot_token'
        );

        if (! $token) {
            throw new RuntimeException(
                'TELEGRAM_BOT_TOKEN belum dikonfigurasi.'
            );
        }

        return $token;
    }

    public function sendMessage(
        string $chatId,
        string $message
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Telegram sendMessage membatasi teks.
        |--------------------------------------------------------------------------
        */

        $message = mb_substr(
            $message,
            0,
            4096
        );

        $response = Http::connectTimeout(5)->timeout(15)
            ->post(
                'https://api.telegram.org/bot'
                .$this->token()
                .'/sendMessage',
                [
                    'chat_id' => $chatId,

                    'text' => $message,
                ]
            );

        return $this->handleResponse(
            $response
        );
    }

    protected function handleResponse(
        Response $response
    ): array {
        if ($response->failed()) {
            throw new RuntimeException(
                'Telegram HTTP error: '
                .$response->status()
            );
        }

        $payload =
            $response->json();

        if (
            ! is_array($payload)
            ||
            ! ($payload['ok'] ?? false)
        ) {
            throw new RuntimeException(
                'Telegram menolak pengiriman: '
            );
        }

        return $payload;
    }

    public function getUpdates(
        ?int $offset = null,
        int $timeout = 20
    ): array {
        $payload = [
            'timeout' => $timeout,

            'allowed_updates' => [
                'message',
            ],
        ];

        if ($offset !== null) {
            $payload['offset'] =
                $offset;
        }

        $response =
            Http::connectTimeout(5)->timeout(
                $timeout + 5
            )
                ->get(
                    'https://api.telegram.org/bot'
                    .$this->token()
                    .'/getUpdates',
                    $payload
                );

        $result =
            $this->handleResponse(
                $response
            );

        return $result['result'] ?? [];
    }
}
