<?php

namespace App\Console\Commands;

use App\Services\TelegramLinkService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Throwable;

class TelegramPollUpdates extends Command
{
    protected $signature =
        'telegram:poll';

    protected $description =
        'Menerima update Telegram Bot untuk development';


    public function handle(
        TelegramService $telegram,
        TelegramLinkService $linkService
    ): int {
        $this->info(
            'Telegram polling berjalan...'
        );

        $this->newLine();

        $this->line(
            'Tekan Ctrl+C untuk berhenti.'
        );


        while (true) {

            try {

                $offset =
                    Cache::get(
                        'telegram.last_update_id'
                    );

                $updates =
                    $telegram->getUpdates(
                        $offset
                            ? ((int) $offset + 1)
                            : null,
                        20
                    );


                foreach ($updates as $update) {

                    $updateId =
                        data_get(
                            $update,
                            'update_id'
                        );

                    if ($updateId !== null) {
                        Cache::forever(
                            'telegram.last_update_id',
                            $updateId
                        );
                    }


                    $this->processUpdate(
                        $update,
                        $telegram,
                        $linkService
                    );
                }

            } catch (Throwable $exception) {

                $this->error(
                    '[' .
                    now()->format(
                        'H:i:s'
                    )
                    . '] '
                    . $exception
                        ->getMessage()
                );

                sleep(3);
            }
        }

        return self::SUCCESS;
    }


    protected function processUpdate(
        array $update,
        TelegramService $telegram,
        TelegramLinkService $linkService
    ): void {
        $message =
            data_get(
                $update,
                'message'
            );

        if (! is_array($message)) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Kita hanya menerima private chat
        |--------------------------------------------------------------------------
        */

        if (
            data_get(
                $message,
                'chat.type'
            ) !== 'private'
        ) {
            return;
        }


        $chatId =
            data_get(
                $message,
                'chat.id'
            );

        $text =
            trim(
                (string)
                data_get(
                    $message,
                    'text',
                    ''
                )
            );


        if (
            ! $chatId
            ||
            $text === ''
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | /start TOKEN
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^\/start(?:@\w+)?(?:\s+([A-Za-z0-9_-]+))?$/',
                $text,
                $matches
            )
            !== 1
        ) {
            return;
        }


        $rawToken =
            $matches[1] ?? null;


        /*
        |--------------------------------------------------------------------------
        | User membuka bot tanpa token
        |--------------------------------------------------------------------------
        */

        if (! $rawToken) {

            $telegram->sendMessage(
                (string) $chatId,
                implode(
                    PHP_EOL,
                    [
                        'SIMOPRAM',
                        '',
                        'Untuk menghubungkan akun Telegram, buka SIMOPRAM lalu pilih menu Hubungkan Telegram.',
                    ]
                )
            );

            return;
        }


        try {

            $linkService->consume(
                $rawToken,
                (string) $chatId,
                data_get(
                    $message,
                    'from',
                    []
                )
            );


            $telegram->sendMessage(
                (string) $chatId,
                implode(
                    PHP_EOL,
                    [
                        '✅ Telegram berhasil dihubungkan.',
                        '',
                        'Anda sekarang dapat menerima pengumuman SIMOPRAM melalui Telegram.',
                    ]
                )
            );


            $this->info(
                sprintf(
                    '[%s] Telegram %s berhasil terhubung.',
                    now()->format(
                        'H:i:s'
                    ),
                    $chatId
                )
            );

        } catch (
            ValidationException $exception
        ) {

            $message =
                collect(
                    $exception->errors()
                )
                    ->flatten()
                    ->first()
                ?? 'Gagal menghubungkan Telegram.';


            $telegram->sendMessage(
                (string) $chatId,
                '❌ ' . $message
            );

        }
    }
}