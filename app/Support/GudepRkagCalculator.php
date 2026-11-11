<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class GudepRkagCalculator
{
    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{lines: array<int, array{description: string, volume: int, unit: string, unit_price: int, subtotal: int}>, total: int}
     */
    public static function calculate(array $lines): array
    {
        if (count($lines) > 30) {
            throw new InvalidArgumentException('Maksimal 30 rincian anggaran per butir.');
        }

        $normalized = [];
        $total = 0;

        foreach ($lines as $line) {
            $description = trim((string) ($line['description'] ?? ''));
            $unit = trim((string) ($line['unit'] ?? ''));
            $volume = $line['volume'] ?? null;
            $price = $line['unit_price'] ?? null;

            if ($description === '' || $unit === '' || ! is_numeric($volume) || ! is_numeric($price)
                || filter_var($volume, FILTER_VALIDATE_INT) === false
                || filter_var($price, FILTER_VALIDATE_INT) === false) {
                throw new InvalidArgumentException('Setiap rincian harus memiliki uraian, volume bulat, satuan, dan harga satuan bulat.');
            }

            $volume = (int) $volume;
            $price = (int) $price;

            if ($volume < 1 || $volume > 999999 || $price < 0 || $price > 999999999999
                || mb_strlen($description) > 200 || mb_strlen($unit) > 30) {
                throw new InvalidArgumentException('Rincian anggaran melewati batas volume, harga, atau panjang teks.');
            }

            $subtotal = $volume * $price;
            if ($subtotal > 999999999999 || $total + $subtotal > 999999999999) {
                throw new InvalidArgumentException('Jumlah rincian anggaran maksimal Rp999.999.999.999.');
            }

            $normalized[] = [
                'description' => $description,
                'volume' => $volume,
                'unit' => $unit,
                'unit_price' => $price,
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }

        return ['lines' => $normalized, 'total' => $total];
    }
}
