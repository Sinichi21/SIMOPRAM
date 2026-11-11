<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\GudepRkagActualComparison;
use PHPUnit\Framework\TestCase;

class GudepRkagActualComparisonTest extends TestCase
{
    public function test_exact_normalized_category_matching_never_merges_different_categories(): void
    {
        $program = [
            ['section' => 'keuangan', 'description' => 'Persami', 'finance_type' => 'expense', 'amount' => 600000],
            ['section' => 'keuangan', 'description' => 'PERSAMI', 'finance_type' => 'expense', 'amount' => 200000],
            ['section' => 'keuangan', 'description' => 'Iuran anggota', 'finance_type' => 'income', 'amount' => 1000000],
            ['section' => 'keuangan', 'description' => 'Peralatan', 'finance_type' => 'expense', 'amount' => 300000],
            ['section' => 'keuangan', 'description' => 'Usaha Dana', 'finance_type' => 'fundraising', 'amount' => 100000],
        ];
        $actual = [
            ['category' => 'persami', 'direction' => 'expense', 'amount' => 550000],
            ['category' => 'Iuran Anggota', 'direction' => 'income', 'amount' => 900000],
            ['category' => 'Persami - Tambahan', 'direction' => 'expense', 'amount' => 900000],
        ];
        $rows = GudepRkagActualComparison::compare($program, $actual);

        $this->assertCount(3, $rows);
        $this->assertSame('Persami', $rows[1]['category']);
        $this->assertSame(800000, $rows[1]['planned']);
        $this->assertSame(550000, $rows[1]['actual']);
        $this->assertSame(250000, $rows[1]['variance']);
        $this->assertTrue($rows[1]['matched']);

        $this->assertSame('Peralatan', $rows[0]['category']);
        $this->assertFalse($rows[0]['matched']);
        $this->assertSame(0, $rows[0]['actual']);
        $this->assertSame(900000, $rows[2]['actual']);
    }

    public function test_other_program_sections_cannot_be_counted_as_financial_plans(): void
    {
        $rows = GudepRkagActualComparison::compare([
            ['section' => 'siaga', 'description' => 'Pesta Siaga', 'finance_type' => 'expense', 'amount' => 120000],
        ], [['direction' => 'expense', 'category' => 'Pesta Siaga', 'amount' => 200000]]);
        $this->assertSame([], $rows);
    }
}
