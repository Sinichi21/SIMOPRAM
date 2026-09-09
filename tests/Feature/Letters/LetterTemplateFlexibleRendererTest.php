<?php

namespace Tests\Feature\Letters;

use App\Models\LetterTemplate;
use App\Services\LetterTemplateRenderer;
use Tests\TestCase;

class LetterTemplateFlexibleRendererTest extends TestCase
{
    public function test_system_placeholders_are_not_reported_missing(): void
    {
        $template = new LetterTemplate([
            'body_template' => '<p>Nomor {{ letter_number }}</p><p>{{ subject }}</p>',
            'requires_recipient' => false,
        ]);

        $missing = app(LetterTemplateRenderer::class)->missingPlaceholders($template, []);

        $this->assertSame([], $missing);
    }

    public function test_recipient_is_required_only_when_template_requires_it(): void
    {
        $template = new LetterTemplate([
            'body_template' => '<p>Yth. {{ recipient }}</p>',
            'requires_recipient' => true,
        ]);

        $missing = app(LetterTemplateRenderer::class)->missingPlaceholders($template, []);
        $this->assertContains('recipient', $missing);

        $template->requires_recipient = false;
        $missing = app(LetterTemplateRenderer::class)->missingPlaceholders($template, []);
        $this->assertSame([], $missing);
    }

    public function test_rich_layout_and_table_are_preserved_safely(): void
    {
        $template = new LetterTemplate([
            'body_template' => '<p style="text-align: center"><strong>SURAT TUGAS</strong></p><table><tr><td>{{ subject }}</td></tr></table>',
            'requires_recipient' => false,
        ]);

        $html = app(LetterTemplateRenderer::class)->render($template, ['subject' => 'Kegiatan'], false);

        $this->assertStringContainsString('text-align: center', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('Kegiatan', $html);
    }
}
