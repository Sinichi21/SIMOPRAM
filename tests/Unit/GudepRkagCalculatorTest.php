<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\GudepRkagCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GudepRkagCalculatorTest extends TestCase
{
    public function test_volume_times_price_builds_consistent_totals(): void
    {
        $result = GudepRkagCalculator::calculate([
            ['description' => '  Konsumsi Persami ', 'volume' => '25', 'unit' => 'orang', 'unit_price' => '15000'],
            ['description' => 'Tali', 'volume' => 3, 'unit' => 'buah', 'unit_price' => 12000],
        ]);
        $this->assertSame(411000, $result['total']);
        $this->assertSame('Konsumsi Persami', $result['lines'][0]['description']);
        $this->assertSame(375000, $result['lines'][0]['subtotal']);
        $this->assertSame(36000, $result['lines'][1]['subtotal']);
    }

    public function test_negative_fractional_or_oversized_inputs_are_rejected(): void
    {
        foreach ([
            ['description' => 'X', 'volume' => -1, 'unit' => 'buah', 'unit_price' => 1],
            ['description' => 'X', 'volume' => '1.5', 'unit' => 'buah', 'unit_price' => 1],
            ['description' => 'X', 'volume' => 1, 'unit' => 'buah', 'unit_price' => -100],
            ['description' => 'X', 'volume' => 2, 'unit' => 'buah', 'unit_price' => 999999999999],
            ['description' => '', 'volume' => 1, 'unit' => 'buah', 'unit_price' => 1],
        ] as $bad) {
            try {
                GudepRkagCalculator::calculate([$bad]);
                $this->fail('Expected invalid budget line to be rejected');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_funding_source_and_ceiling_track_overage_without_changing_total(): void
    {
        $result = GudepRkagCalculator::calculate([[
            'description' => 'Konsumsi Persami',
            'volume' => 25,
            'unit' => 'orang',
            'unit_price' => 15000,
            'funding_source' => ' Iuran Anggota ',
            'ceiling' => 350000,
        ]]);

        $this->assertSame(375000, $result['total']);
        $this->assertSame('Iuran Anggota', $result['lines'][0]['funding_source']);
        $this->assertSame(350000, $result['lines'][0]['ceiling']);
        $this->assertSame(25000, $result['lines'][0]['over_ceiling']);

        $withoutCeiling = GudepRkagCalculator::calculate([[
            'description' => 'Tali', 'volume' => 3, 'unit' => 'buah',
            'unit_price' => 12000,
        ]]);
        $this->assertSame('', $withoutCeiling['lines'][0]['funding_source']);
        $this->assertNull($withoutCeiling['lines'][0]['ceiling']);
        $this->assertSame(0, $withoutCeiling['lines'][0]['over_ceiling']);
    }

    public function test_invalid_ceiling_is_rejected_without_creating_any_line(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GudepRkagCalculator::calculate([[
            'description' => 'Konsumsi', 'volume' => 25,
            'unit' => 'orang', 'unit_price' => 15000,
            'ceiling' => -1,
        ]]);
    }

    public function test_zero_lines_are_valid_and_max_thirty_lines_are_enforced(): void
    {
        $this->assertSame(['lines' => [], 'total' => 0], GudepRkagCalculator::calculate([]));
        $this->expectException(InvalidArgumentException::class);
        GudepRkagCalculator::calculate(array_fill(0, 31, [
            'description' => 'Tali', 'volume' => 1, 'unit' => 'buah', 'unit_price' => 2000,
        ]));
    }
}
