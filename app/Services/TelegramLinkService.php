<?php

namespace App\Services;

use App\Models\TelegramLinkToken;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TelegramLinkService
{
    /*
    |--------------------------------------------------------------------------
    | Buat link Telegram
    |--------------------------------------------------------------------------
    */

    public function createLink(
        User $user
    ): string {
        $schoolId =
            app(SchoolContext::class)
                ->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        $botUsername =
            config(
                'services.telegram.bot_username'
            );

        if (! $botUsername) {
            throw ValidationException::withMessages([
                'telegram' => 'TELEGRAM_BOT_USERNAME belum dikonfigurasi.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Hapus token lama yang belum dipakai
        |--------------------------------------------------------------------------
        */

        TelegramLinkToken::query()
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'user_id',
                $user->id
            )
            ->whereNull(
                'used_at'
            )
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | 32 random bytes → base64url
        |
        | Hasil sekitar 43 karakter.
        | Cocok dengan batas start parameter Telegram.
        |--------------------------------------------------------------------------
        */

        $rawToken =
            rtrim(
                strtr(
                    base64_encode(
                        random_bytes(32)
                    ),
                    '+/',
                    '-_'
                ),
                '='
            );

        TelegramLinkToken::query()
            ->create([
                'school_id' => $schoolId,

                'user_id' => $user->id,

                'token_hash' => hash(
                    'sha256',
                    $rawToken
                ),

                'expires_at' => now()->addMinutes(10),
            ]);

        return sprintf(
            'https://t.me/%s?start=%s',
            ltrim(
                $botUsername,
                '@'
            ),
            $rawToken
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Hubungkan update Telegram dengan akun SIMPRAM
    |--------------------------------------------------------------------------
    */

    public function consume(
        string $rawToken,
        string $chatId,
        array $telegramUser = []
    ): UserNotificationChannel {
        $tokenHash =
            hash(
                'sha256',
                $rawToken
            );

        $linkToken =
            TelegramLinkToken::query()
                ->where(
                    'token_hash',
                    $tokenHash
                )
                ->first();

        if (! $linkToken) {
            throw ValidationException::withMessages([
                'telegram' => 'Token penghubung Telegram tidak valid.',
            ]);
        }

        if ($linkToken->isUsed()) {
            throw ValidationException::withMessages([
                'telegram' => 'Token Telegram sudah digunakan.',
            ]);
        }

        if ($linkToken->isExpired()) {
            throw ValidationException::withMessages([
                'telegram' => 'Token Telegram sudah kedaluwarsa. Buat link baru dari SIMPRAM.',
            ]);
        }

        return DB::transaction(
            function () use (
                $linkToken,
                $chatId,
                $telegramUser
            ): UserNotificationChannel {

                /*
                |--------------------------------------------------------------------------
                | Cegah satu Telegram terhubung ke user lain
                | pada sekolah yang sama.
                |--------------------------------------------------------------------------
                */

                $usedByOtherUser =
                    UserNotificationChannel::query()
                        ->where(
                            'school_id',
                            $linkToken->school_id
                        )
                        ->where(
                            'channel',
                            'telegram'
                        )
                        ->where(
                            'destination',
                            $chatId
                        )
                        ->where(
                            'user_id',
                            '!=',
                            $linkToken->user_id
                        )
                        ->exists();

                if ($usedByOtherUser) {
                    throw ValidationException::withMessages([
                        'telegram' => 'Akun Telegram ini sudah terhubung dengan akun SIMPRAM lain.',
                    ]);
                }

                $channel =
                    UserNotificationChannel::query()
                        ->updateOrCreate(
                            [
                                'school_id' => $linkToken->school_id,

                                'user_id' => $linkToken->user_id,

                                'channel' => 'telegram',
                            ],
                            [
                                'destination' => $chatId,

                                'is_verified' => true,

                                'is_active' => true,

                                'verified_at' => now(),

                                'metadata' => [
                                    'telegram_user_id' => $telegramUser[
                                            'id'
                                        ] ?? null,

                                    'username' => $telegramUser[
                                            'username'
                                        ] ?? null,

                                    'first_name' => $telegramUser[
                                            'first_name'
                                        ] ?? null,

                                    'last_name' => $telegramUser[
                                            'last_name'
                                        ] ?? null,
                                ],
                            ]
                        );

                $linkToken->update([
                    'used_at' => now(),
                ]);

                return $channel;
            }
        );
    }
}
