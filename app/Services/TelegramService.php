<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramService
{
    protected function token(): string
    {
        $token = config(
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

        $response = Http::timeout(15)
            ->retry(
                2,
                500
            )
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
                .' - '
                .$response->body()
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
                .json_encode($payload)
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
            Http::timeout(
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
