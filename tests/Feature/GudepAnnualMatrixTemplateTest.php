<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class GudepAnnualMatrixTemplateTest extends TestCase
{
    public function test_annual_matrix_matches_reference_with_finance_in_same_table_and_no_month_cells_for_section_six(): void
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
        $this->assertStringNotContainsString('F. BIDANG KEUANGAN', $rendered);
        $this->assertSame(1, substr_count($rendered, '<table border="1"'));
        $this->assertSame(12, substr_count($rendered, '<col style="width:2.75%;">'));
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
        $this->assertStringContainsString('Denpasar, 09/11/2026', $html);
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
