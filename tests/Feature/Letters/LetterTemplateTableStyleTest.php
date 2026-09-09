<?php

namespace Tests\Feature\Letters;

use App\Services\LetterTemplateHtmlSanitizer;
use Tests\TestCase;

class LetterTemplateTableStyleTest extends TestCase
{
    public function test_safe_table_width_border_and_vertical_alignment_are_preserved(): void
    {
        $html = <<<'HTML'
<table style="width: 75%; max-width: 100%">
    <tr>
        <td style="width: 30%; border: 0.6pt solid #000; vertical-align: middle">A</td>
        <td style="width: 70%; border: 0; vertical-align: bottom">B</td>
    </tr>
</table>
HTML;

        $clean = app(LetterTemplateHtmlSanitizer::class)->sanitize($html);

        $this->assertStringContainsString('width: 75%', $clean);
        $this->assertStringContainsString('width: 30%', $clean);
        $this->assertStringContainsString('border: 0.6pt solid #000', $clean);
        $this->assertStringContainsString('vertical-align: middle', $clean);
        $this->assertStringContainsString('vertical-align: bottom', $clean);
        $this->assertStringContainsString('border: 0', $clean);
    }
}
