<?php

declare(strict_types=1);

namespace Tests\Feature;

use Dompdf\Dompdf;
use Dompdf\FrameDecorator\Table;
use Tests\TestCase;

class GudepAnnualMatrixTemplateTest extends TestCase
{
    public function test_merged_number_does_not_drop_activities_when_matrix_spans_pages(): void
    {
        $entries = [];
        for ($index = 1; $index <= 70; $index++) {
            $entries[] = ['key' => 'activity-'.$index, 'section' => 'siaga', 'description' => 'Kegiatan unik '.$index];
        }
        for ($index = 1; $index <= 40; $index++) {
            $entries[] = ['key' => 'expense-'.$index, 'section' => 'keuangan', 'finance_type' => 'expense', 'amount' => 1000, 'description' => 'Biaya unik '.$index];
        }
        $html = view('administration.partials.annual-program-matrix', ['entries' => $entries])->render();
        $pdf = new Dompdf;
        $text = '';
        $pageText = [];
        $activityPositions = [];
        $pdf->setCallbacks([[
            'event' => 'end_frame',
            'f' => function ($frame, $canvas) use (&$text, &$pageText, &$activityPositions): void {
                if ($frame->get_node()->nodeName === '#text') {
                    $text .= $frame->get_node()->textContent.' ';
                    $page = $canvas->get_page_number();
                    $pageText[$page] = ($pageText[$page] ?? '').$frame->get_node()->textContent.' ';
                    if (str_contains($frame->get_node()->textContent, 'Kegiatan unik') || str_contains($frame->get_node()->textContent, 'Biaya unik')) {
                        $cell = $frame->get_parent();
                        while ($cell !== null && $cell->get_node()->nodeName !== 'td') {
                            $cell = $cell->get_parent();
                        }
                        if ($cell !== null) {
                            $activityPositions[] = $cell->get_position()['x'];
                        }
                    }
                }
            },
        ]]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->loadHtml('<!doctype html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>');

        $pdf->render();

        $this->assertGreaterThan(1, $pdf->getCanvas()->get_page_count());
        foreach ($entries as $entry) {
            $this->assertStringContainsString($entry['description'].' ', $text);
        }
        $this->assertStringContainsString('Kekurangan rencana biaya sebesar Rp 40.000', $text);
        $this->assertCount(110, $activityPositions);
        foreach ($activityPositions as $position) {
            $this->assertEqualsWithDelta($activityPositions[0], $position, 0.1);
        }
        $lastPageText = $pageText[array_key_last($pageText)];
        $this->assertStringContainsString('BIDANG KEUANGAN', $lastPageText);
        $this->assertStringContainsString('(Rp,-)', $lastPageText);
        $this->assertStringNotContainsString('BULAN', $lastPageText);
        $this->assertStringNotContainsString('S/G/T/D', $lastPageText);
    }

    public function test_rendered_pdf_keeps_month_columns_equal_and_description_wide(): void
    {
        $html = view('administration.partials.annual-program-matrix', [
            'entries' => [['key' => 'a', 'section' => 'siaga', 'description' => str_repeat('Latihan peserta didik ', 12), 'notes' => str_repeat('Keterangan ', 10)]],
            'programNumber' => '04.237', 'yearName' => '2026/2027',
        ])->render();
        $pdf = new Dompdf;
        $columns = [];
        $pdf->setCallbacks([[
            'event' => 'end_frame',
            'f' => function ($frame) use (&$columns): void {
                while ($frame !== null) {
                    $table = $frame instanceof Table ? $frame : $frame->get_decorator();
                    if ($table instanceof Table) {
                        $columns = $table->get_cellmap()->get_columns();
                        break;
                    }
                    $frame = $frame->get_parent();
                }
            },
        ]]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->loadHtml('<!doctype html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>');

        $pdf->render();

        $columns = array_values(array_filter($columns, fn (array $column): bool => $column['used-width'] !== null));
        $this->assertCount(16, $columns);
        $totalWidth = array_sum(array_column($columns, 'used-width'));
        foreach ([4, 43, ...array_fill(0, 12, 2.5), 12, 11] as $index => $percent) {
            $this->assertEqualsWithDelta($percent, $columns[$index]['used-width'] / $totalWidth * 100, 0.2);
        }
    }

    public function test_annual_matrix_keeps_finance_aligned_with_separate_repeating_header(): void
    {
        $entries = [
            [
                'key' => 'a', 'section' => 'siaga', 'parent_key' => null,
                'description' => 'Latihan mencapai Siaga Mula', 'quantity' => 38,
                'months' => [true, false, false, false, false, false, false, false, false, false, false, true],
            ],
            [
                'key' => 'b', 'section' => 'bersama', 'parent_key' => null,
                'description' => 'Bakti masyarakat', 'quantity' => 83,
            ],
            [
                'key' => 'sarana-row', 'section' => 'sarana', 'parent_key' => null,
                'description' => 'Perlengkapan Gugusdepan', 'quantity' => 8,
            ],
            [
                'key' => 'c', 'section' => 'keuangan', 'parent_key' => null,
                'description' => 'Rencana Pemasukan',
            ],
            [
                'key' => 'd', 'section' => 'keuangan', 'parent_key' => 'c',
                'description' => 'Iuran anggota', 'finance_type' => 'income', 'amount' => 7968000,
            ],
            [
                'key' => 'e', 'section' => 'keuangan', 'parent_key' => null,
                'description' => 'Rencana Pengeluaran',
            ],
            [
                'key' => 'f', 'section' => 'keuangan', 'parent_key' => 'e',
                'description' => 'Biaya kegiatan Gugusdepan', 'finance_type' => 'expense', 'amount' => 14000000,
            ],
        ];

        $rendered = view('administration.partials.annual-program-matrix', [
            'entries' => $entries,
            'kwarcab' => 'Denpasar',
            'programNumber' => '04.237',
            'yearName' => '2026-2027',
        ])->render();

        $this->assertStringContainsString('GUGUSDEPAN DENPASAR 04.237', $rendered);
        $this->assertStringContainsString('KEGIATAN DAN LATIHAN PESERTA DIDIK', $rendered);
        $this->assertStringContainsString('BIDANG SARANA DAN ADMINISTRASI', $rendered);
        $this->assertStringContainsString('BIDANG KEUANGAN', $rendered);
        $this->assertStringContainsString('colspan="13"', $rendered);
        $this->assertStringContainsString('JUMLAH', $rendered);
        $this->assertStringContainsString('Iuran anggota', $rendered);
        $this->assertStringContainsString('7.968.000', $rendered);
        $this->assertStringContainsString('14.000.000', $rendered);
        $this->assertStringContainsString('Selisih RKAG', $rendered);
        $this->assertStringContainsString('>-6.032.000</td>', $rendered);
        $this->assertStringNotContainsString('F. BIDANG KEUANGAN', $rendered);
        $this->assertSame(2, substr_count($rendered, '<table border="1"'));
        $this->assertSame(12, substr_count($rendered, '<col style="width:2.5%;">'));
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$rendered);
        $xpath = new \DOMXPath($document);
        foreach (['I', 'II', 'V', 'VI'] as $numeral) {
            $cell = $xpath->query('//tr/td[normalize-space(.)="'.$numeral.'"]')->item(0);
            $this->assertNotNull($cell);
            $this->assertFalse($cell->hasAttribute('rowspan'));
            $this->assertStringContainsString('border-bottom:0', $cell->getAttribute('style'));
        }
    }

    public function test_matrix_fundraising_contains_shortfall_only_once_and_preserves_actual_activity(): void
    {
        $entries = [
            ['key' => 'income', 'section' => 'keuangan', 'parent_key' => null, 'description' => 'Rencana Pemasukan'],
            ['key' => 'i1', 'section' => 'keuangan', 'parent_key' => 'income', 'description' => 'Iuran anggota', 'finance_type' => 'income', 'amount' => 8648000],
            ['key' => 'expense', 'section' => 'keuangan', 'parent_key' => null, 'description' => 'Rencana Pengeluaran'],
            ['key' => 'e1', 'section' => 'keuangan', 'parent_key' => 'expense', 'description' => 'Biaya kegiatan', 'finance_type' => 'expense', 'amount' => 10000000],
            ['key' => 'fundraiser', 'section' => 'keuangan', 'parent_key' => null, 'description' => 'Usaha Dana', 'finance_type' => 'fundraising',
                'detail' => 'Kekurangan rencana biaya sebesar Rp. 1.352.000 diupayakan dengan mengadakan Usaha Dana. Sumber usaha dana dari kegiatan Bazar Ceria.'],
        ];
        $html = view('administration.partials.annual-program-matrix', [
            'entries' => $entries, 'programNumber' => '04.487', 'kwarcab' => 'Denpasar',
            'yearName' => '2026/2027',
        ])->render();

        $this->assertSame(1, substr_count($html, '3. Usaha Dana'));
        $this->assertSame(1, substr_count($html, 'Kekurangan rencana biaya sebesar Rp 1.352.000'));
        $this->assertStringContainsString('kegiatan Bazar Ceria', $html);
        $this->assertStringNotContainsString('Usaha Dana.', $html);
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $fundraisingCell = $xpath->query('//td[contains(., "Kekurangan rencana biaya sebesar Rp")]')->item(0);
        $this->assertNotNull($fundraisingCell);
        $this->assertSame('15', $fundraisingCell->getAttribute('colspan'));
        $this->assertSame(0, $fundraisingCell->getElementsByTagName('div')->length);
    }

    public function test_matrix_shows_positive_rkag_surplus_and_does_not_invent_shortfall(): void
    {
        $entries = [
            ['key' => 'i', 'section' => 'keuangan', 'parent_key' => null, 'description' => 'Rencana Pemasukan'],
            ['key' => 'i-1', 'section' => 'keuangan', 'parent_key' => 'i', 'description' => 'Iuran', 'finance_type' => 'income', 'amount' => 12000000],
            ['key' => 'e', 'section' => 'keuangan', 'parent_key' => null, 'description' => 'Rencana Pengeluaran'],
            ['key' => 'e-1', 'section' => 'keuangan', 'parent_key' => 'e', 'description' => 'Peralatan', 'finance_type' => 'expense', 'amount' => 7000000],
        ];

        $html = view('administration.partials.annual-program-matrix', [
            'entries' => $entries, 'programNumber' => '04.237',
            'kwarcab' => 'Denpasar', 'yearName' => '2026/2027',
        ])->render();

        $this->assertStringContainsString('Selisih RKAG (Rencana Pemasukan - Rencana Pengeluaran)', $html);
        $this->assertStringContainsString('>5.000.000</td>', $html);
        $this->assertStringContainsString('Tidak terdapat kekurangan rencana biaya.', $html);
        $this->assertStringNotContainsString('Kekurangan rencana biaya sebesar Rp', $html);
    }

    public function test_official_letterhead_uses_existing_persuratan_renderer_and_putra_number(): void
    {
        $html = view('administration.partials.gudep-document-letterhead', [
            'letterhead' => [
                'type' => 'male',
                'title' => 'GERAKAN PRAMUKA',
                'subtitle' => '',
                'address' => 'Jalan Contoh, Denpasar',
                'school_name' => 'SD 21 Pemecutan',
                'school_city' => 'Denpasar',
                'school_address' => 'Jalan Contoh',
                'postal_code' => '80100',
                'gudep_address' => 'Jalan Contoh',
                'male_number' => '04.487',
                'female_number' => '04.488',
            ],
        ])->render();

        $this->assertStringContainsString('GERAKAN PRAMUKA', $html);
        $this->assertStringContainsString('GUGUSDEPAN DENPASAR 04.487', $html);
        $this->assertStringContainsString('PANGKALAN SD 21 PEMECUTAN', $html);
        $this->assertStringNotContainsString('GUGUSDEPAN DENPASAR 04.488', $html);
    }

    public function test_signature_block_keeps_two_signers_together_and_shows_only_existing_identifier(): void
    {
        $html = view('administration.partials.program-signatures', [
            'programSignatures' => [
                'acknowledger' => ['name' => 'Kamabigus', 'position' => 'Ketua Mabigus', 'identity' => 'NIP. 198001012006041001'],
                'signer' => ['name' => 'Ketua Gudep', 'position' => 'Ketua Gudep', 'identity' => 'NTA. 12345678'],
            ],
            'programCity' => 'Denpasar',
            'programNumber' => '04.237',
            'programDate' => '09/11/2026',
        ])->render();

        $this->assertStringContainsString('page-break-inside:avoid', $html);
        $this->assertStringContainsString('Majelis Pembimbing Gugusdepan', $html);
        $this->assertStringContainsString('Gugusdepan Denpasar 04.237', $html);
        $this->assertStringContainsString('Denpasar, 9 November 2026', $html);
        $this->assertStringNotContainsString('09/11/2026', $html);
        $this->assertStringContainsString('NIP. 198001012006041001', $html);
        $this->assertStringContainsString('NTA. 12345678', $html);
        $this->assertSame(2, substr_count($html, 'Ketua,'));

        $emptyIdentity = view('administration.partials.program-signatures', [
            'programSignatures' => [
                'acknowledger' => ['name' => 'Kamabigus', 'position' => 'Ketua Mabigus'],
                'signer' => ['name' => 'Ketua Gudep', 'position' => 'Ketua Gudep'],
            ],
        ])->render();
        $this->assertStringNotContainsString('NTA.', $emptyIdentity);
        $this->assertStringNotContainsString('NIP.', $emptyIdentity);
    }
}
