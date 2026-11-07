<?php

declare(strict_types=1);

namespace App\Support;

use Dompdf\Dompdf;

final class OfficialDocumentLastPageFooter
{
    /** @param array<int, string> $copies */
    public static function attach(Dompdf $dompdf, array $copies): void
    {
        $copies = array_values(array_slice(array_filter(array_map(
            static fn ($value): string => trim((string) $value), $copies
        ), static fn (string $value): bool => $value !== ''), 0, 4));

        $dompdf->getCanvas()->page_script(static function (
            int $pageNumber,
            int $pageCount,
            $canvas,
            $fontMetrics
        ) use ($copies): void {
            if ($pageNumber !== $pageCount) {
                return;
            }

            $height = $canvas->get_height();
            $textFont = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $boldFont = $fontMetrics->getFont('DejaVu Sans', 'bold');
            $italicFont = $fontMetrics->getFont('DejaVu Sans', 'italic');
            $x = 48;

            if ($copies !== []) {
                $canvas->text($x, $height - 164, 'Tembusan:', $boldFont, 7.8, [0.23, 0.23, 0.26]);
                foreach ($copies as $index => $recipient) {
                    // Long display names are bounded in the input to keep the
                    // archive footer within the A4 page.
                    $canvas->text($x + 8, $height - 153 + $index * 10,
                        ($index + 1).'. '.mb_strimwidth($recipient, 0, 95, '…'),
                        $textFont, 7.4, [0.25, 0.25, 0.27]);
                }
            }

            $notes = [
                'Dokumen ini memuat snapshot program yang diterbitkan oleh SIMPRAM. Perubahan selanjutnya',
                'pada program atau agenda tidak mengubah arsip versi ini. QR verifikasi, nomor revisi,',
                'dan metadata penerbitan disertakan melalui sistem penerbitan dokumen.',
            ];
            foreach ($notes as $index => $line) {
                $canvas->text($x, $height - 105 + $index * 8.5,
                    $line, $italicFont, 6.4, [0.36, 0.36, 0.39]);
            }
        });
    }
}
