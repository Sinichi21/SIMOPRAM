<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Shared geometry for the last-page archive note, copy recipients and QR.
 * Points are measured from the top of the physical page by Dompdf's canvas.
 */
final class OfficialDocumentFooterLayout
{
    public static function reserveBottomMm(bool $hasCopies): int
    {
        return $hasCopies ? 63 : 42;
    }

    public static function qrBottomOffsetMm(bool $hasCopies): int
    {
        return $hasCopies ? -56 : -35;
    }

    /**
     * @return array{copies_title: float, copies_first: float, copies_step: float, notice_first: float, notice_step: float}
     */
    public static function positions(float $pageHeight): array
    {
        return [
            'copies_title' => $pageHeight - 164,
            'copies_first' => $pageHeight - 153,
            'copies_step' => 10,
            'notice_first' => $pageHeight - 105,
            'notice_step' => 8.5,
        ];
    }
}
