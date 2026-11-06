<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\GudepAnnualProgram;
use PHPUnit\Framework\TestCase;

class GudepAnnualProgramTest extends TestCase
{
    public function test_template_only_populates_available_levels_and_keeps_nested_sku_skk_items(): void
    {
        $rows = GudepAnnualProgram::template(['Siaga']);
        $siaga = GudepAnnualProgram::numbered($rows, 'siaga');

        $this->assertNotEmpty($siaga);
        $this->assertSame([], GudepAnnualProgram::numbered($rows, 'penggalang'));
        $this->assertSame([], GudepAnnualProgram::numbered($rows, 'penegak'));

        $sku = collect($siaga)->first(fn (array $row): bool => $row['entry']['description'] === 'Pencapaian SKU');
        $mula = collect($siaga)->first(fn (array $row): bool => $row['entry']['description'] === 'Mula');
        $skk = collect($siaga)->first(fn (array $row): bool => $row['entry']['description'] === 'Pencapaian SKK');

        $this->assertSame('a.', $sku['marker']);
        $this->assertSame(0, $sku['depth']);
        $this->assertSame('1)', $mula['marker']);
        $this->assertSame(1, $mula['depth']);
        $this->assertSame($sku['key'], $mula['entry']['parent_key']);
        $this->assertSame('b.', $skk['marker']);
        $this->assertStringContainsString('10 macam', $skk['entry']['detail']);
    }

    public function test_finance_sections_follow_document_numbering_and_calculate_idr_totals(): void
    {
        $rows = GudepAnnualProgram::template([]);
        $finance = GudepAnnualProgram::numbered($rows, 'keuangan');

        $this->assertSame('1.', $finance[0]['marker']);
        $this->assertSame('Rencana Pemasukan', $finance[0]['entry']['description']);
        $this->assertSame('a.', $finance[1]['marker']);
        $this->assertSame('Iuran anggota', $finance[1]['entry']['description']);

        foreach ($rows as &$row) {
            $row['amount'] = match ($row['description']) {
                'Iuran anggota' => 1500000,
                'Sumbangan Mabigus' => 500000,
                'Biaya kegiatan Perindukan Siaga' => 2500000,
                'Biaya kegiatan Gugusdepan' => 1000000,
                default => null,
            };
        }
        unset($row);

        $this->assertSame([
            'income' => 2000000,
            'expense' => 3500000,
            'shortfall' => 1500000,
            'surplus' => 0,
        ], GudepAnnualProgram::totals($rows));
    }

    public function test_legacy_flat_rows_still_have_regular_numbering(): void
    {
        $rows = [
            ['section' => 'bersama', 'description' => 'Ulang Tahun Gugusdepan', 'quantity' => null],
            ['section' => 'bersama', 'description' => 'Bakti Masyarakat', 'quantity' => 2],
        ];
        $numbered = GudepAnnualProgram::numbered($rows, 'bersama');

        $this->assertSame('1.', $numbered[0]['marker']);
        $this->assertSame('2.', $numbered[1]['marker']);
        $this->assertSame(0, $numbered[1]['depth']);
    }
}
