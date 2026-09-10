<?php

namespace App\Services;

use App\Models\MessagingSetting;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class MailConfigurationService
{
    public function apply(MessagingSetting $setting): void
    {
        if (! $setting->enabled) {
            throw new RuntimeException(
                'Pengiriman email dinonaktifkan oleh Super Admin.'
            );
        }

        $options = $setting->options ?? [];

        if (
            empty($options['host']) ||
            empty($options['port']) ||
            empty($options['from_address'])
        ) {
            throw new RuntimeException(
                'Konfigurasi SMTP belum lengkap.'
            );
        }

        $security = match ($options['scheme'] ?? 'none') {
            /*
             * SMTP lokal:
             * Laravel -> 127.0.0.1:25 -> Postfix
             *
             * Tidak menggunakan STARTTLS karena komunikasi
             * hanya melalui loopback VPS.
             */
            'none' => [
                'scheme' => 'smtp',
                'auto_tls' => false,
                'require_tls' => false,
            ],

            /*
             * SMTP Submission:
             * Biasanya port 587 + STARTTLS.
             */
            'smtp' => [
                'scheme' => 'smtp',
                'auto_tls' => true,
                'require_tls' => true,
            ],

            /*
             * Implicit TLS / SMTPS:
             * Biasanya port 465.
             */
            'smtps' => [
                'scheme' => 'smtps',
                'auto_tls' => true,
                'require_tls' => false,
            ],

            default => throw new RuntimeException(
                'Mode keamanan SMTP tidak valid.'
            ),
        };

        config([
            'mail.default' => 'messaging',

            'mail.mailers.messaging' => [
                'transport' => 'smtp',

                'scheme' => $security['scheme'],

                'host' => $options['host'],
                'port' => (int) $options['port'],

                'username' => filled($options['username'] ?? null)
                    ? $options['username']
                    : null,

                'password' => filled($options['password'] ?? null)
                    ? $options['password']
                    : null,

                'timeout' => 15,

                'auto_tls' => $security['auto_tls'],
                'require_tls' => $security['require_tls'],
            ],

            'mail.from' => [
                'address' => $options['from_address'],
                'name' => $options['from_name'] ?? 'SIMPRAM',
            ],
        ]);

        /*
         * Hapus instance transport lama.
         *
         * Penting karena konfigurasi SMTP dapat diubah
         * secara dinamis melalui dashboard.
         */
        Mail::purge('messaging');
    }
}
