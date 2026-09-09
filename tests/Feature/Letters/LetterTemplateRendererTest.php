<?php

namespace Tests\Feature\Letters;

use App\Models\LetterTemplate;
use App\Services\LetterTemplateRenderer;
use Tests\TestCase;

class LetterTemplateRendererTest extends TestCase
{
    public function test_renderer_extracts_unique_placeholders(): void
    {
        $renderer = app(LetterTemplateRenderer::class);

        $placeholders = $renderer->placeholders('Halo {{ recipient }}, {{activity_name}} untuk {{ recipient }}.');

        $this->assertSame(['recipient', 'activity_name'], $placeholders);
    }

    public function test_renderer_replaces_values_and_marks_missing_for_preview(): void
    {
        $renderer = app(LetterTemplateRenderer::class);
        $template = new LetterTemplate(['body_template' => 'Yth. {{ recipient }}\nKegiatan: {{ activity_name }}']);

        $result = $renderer->render($template, ['recipient' => 'Orang Tua/Wali'], true);

        $this->assertStringContainsString('Orang Tua/Wali', $result);
        $this->assertStringContainsString('[Belum diisi: Nama kegiatan]', $result);
    }
}
