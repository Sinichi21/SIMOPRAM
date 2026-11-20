<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\OfficialDocumentFooterLayout;
use PHPUnit\Framework\TestCase;

class OfficialDocumentFooterLayoutTest extends TestCase
{
    public function test_notice_and_copies_fit_in_reserved_a4_footer_in_both_orientations(): void
    {
        foreach ([595.28, 841.89] as $pageHeight) {
            foreach ([0, 4] as $copies) {
                $reserved = OfficialDocumentFooterLayout::reserveBottomMm($copies > 0) * 72 / 25.4;
                $positions = OfficialDocumentFooterLayout::positions($pageHeight);
                $firstText = $copies ? $positions['copies_title'] : $positions['notice_first'];

                // All footer text must begin below the normal content boundary.
                $this->assertGreaterThan($pageHeight - $reserved, $firstText);

                // Last copy line must end above the first italic disclaimer line.
                if ($copies) {
                    $lastCopy = $positions['copies_first'] + ($copies - 1) * $positions['copies_step'];
                    $this->assertLessThan($positions['notice_first'] - 8, $lastCopy);
                }

                // Footer note finishes above the verification QR's 16mm band.
                $lastNotice = $positions['notice_first'] + 2 * $positions['notice_step'];
                $this->assertLessThan($pageHeight - 75, $lastNotice);
            }
        }
        $this->assertSame(-56, OfficialDocumentFooterLayout::qrBottomOffsetMm(true));
        $this->assertSame(-35, OfficialDocumentFooterLayout::qrBottomOffsetMm(false));
    }
}
