<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class GudepAnnualProgram
{
    /** @return array<string, string> */
    public static function sections(): array
    {
        return [
            'siaga' => 'Siaga',
            'penggalang' => 'Penggalang',
            'penegak' => 'Penegak',
            'pandega' => 'Pandega',
            'bersama' => 'B. KEGIATAN BERSAMA ANTAR SATUAN DALAM GUGUSDEPAN',
            'dewasa' => 'C. BIDANG PENDIDIKAN ORANG DEWASA',
            'penghargaan' => 'D. BIDANG TANDA PENGHARGAAN',
            'sarana' => 'E. BIDANG SARANA DAN ADMINISTRASI',
            'keuangan' => 'F. BIDANG KEUANGAN',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function template(array $availableLevels): array
    {
        $entries = [];
        $append = function (string $section, string $description, ?string $parent = null, ?string $detail = null, ?string $financeType = null) use (&$entries): string {
            $key = (string) Str::uuid();
            $entries[] = [
                'key' => $key, 'parent_key' => $parent, 'section' => $section,
                'description' => $description, 'detail' => $detail,
                'quantity' => null, 'unit' => 'kali',
                'finance_type' => $financeType, 'currency' => 'IDR', 'amount' => null,
            ];

            return $key;
        };

        $available = array_map(fn ($name) => mb_strtolower(trim((string) $name)), $availableLevels);
        foreach ([
            'siaga' => ['Mula', 'Bantu', 'Tata'],
            'penggalang' => ['Ramu', 'Rakit', 'Terap'],
            'penegak' => ['Bantara', 'Laksana'],
            'pandega' => [],
        ] as $section => $ranks) {
            if (in_array($section, $available, true) === false) {
                continue;
            }
            if ($ranks !== []) {
                $sku = $append($section, 'Pencapaian SKU', null, 'Meningkatkan latihan Pramuka '.ucfirst($section).' dari jenjang '.ucfirst($section).':');
                foreach ($ranks as $rank) {
                    $append($section, $rank, $sku);
                }
            }
            $skk = $append($section, 'Pencapaian SKK', null, 'Berusaha untuk pencapaian SKK 10 macam meliputi:');
            foreach ([
                '2 macam SKK Agama',
                '2 macam SKK Patriotisme dan Seni Budaya',
                '2 macam SKK Ketangkasan dan Kesehatan',
                '2 macam SKK Keterampilan dan Teknik Pembangunan',
                '2 macam SKK Sosial, Perikemanusiaan, Gotong Royong, Ketertiban Masyarakat, Perdamaian Dunia dan Lingkungan Hidup',
            ] as $skkItem) {
                $append($section, $skkItem, $skk);
            }
            $append($section, 'Menyiapkan '.ucfirst($section).' Garuda', null, 'Menyiapkan '.ucfirst($section).' Garuda sesuai persyaratan yang berlaku.');
            foreach (match ($section) {
                'siaga' => ['Latihan Pemimpin Barung' => 1, 'Perkemahan Siang Hari' => 2, 'Permainan Besar Siaga' => 1, 'Bazar Siaga' => 1],
                'penggalang' => ['Gladian Pimpinan Regu' => 1, 'Perkemahan Sabtu Minggu' => 4, 'Perkemahan jauh' => 2, 'Lomba Tingkat I' => 1, 'Bakti Masyarakat' => 2],
                'penegak' => ['Gladian Pimpinan Sangga' => 1, 'Pengembaraan' => 1, 'Perkemahan' => 2, 'Perkemahan Bakti/Bakti Masyarakat' => 2],
                default => ['Pengembaraan' => null, 'Perkemahan/Bakti Masyarakat' => null, 'Menyelenggarakan satu proyek ilmiah' => null],
            } as $label => $quantity) {
                $key = $append($section, $label);
                $entries[array_key_last($entries)]['quantity'] = $quantity;
            }
        }

        foreach ([
            'bersama' => ['Ulang Tahun Gugusdepan', 'Hari-hari Besar Agama dan Hari-hari Besar Nasional', 'Bakti Masyarakat di lingkungan Gugusdepan'],
            'dewasa' => ['Mengirim Pembina mengikuti Kursus Pembina yang diselenggarakan Kwartir', 'Mengirim Pembina mengikuti pertemuan Pembina yang diselenggarakan Kwartir'],
            'penghargaan' => ['Sistem Penghargaan dijalankan sebagaimana mestinya', 'TKK untuk Penegak dan Pandega disesuaikan dengan kemajuan zaman'],
            'sarana' => ['Buku-buku pegangan Pembina', 'Perlengkapan Perindukan Siaga', 'Perlengkapan Pasukan Penggalang', 'Perlengkapan Ambalan Penegak', 'Perlengkapan Racana Pandega', 'Sanggar Bakti Gugusdepan', 'Papan Nama Gudep, stempel dan buku administrasi', 'Surat perizinan kegiatan sesuai kebutuhan', 'Asuransi'],
        ] as $section => $items) {
            foreach ($items as $item) {
                $append($section, $item);
            }
        }

        $incomeParent = $append('keuangan', 'Rencana Pemasukan');
        $append('keuangan', 'Iuran anggota', $incomeParent, null, 'income');
        $append('keuangan', 'Sumbangan Mabigus', $incomeParent, null, 'income');
        $expenseParent = $append('keuangan', 'Rencana Pengeluaran');
        foreach (['Biaya kegiatan Perindukan Siaga', 'Biaya kegiatan Penggalang', 'Biaya kegiatan Penegak', 'Biaya kegiatan Pandega', 'Biaya kegiatan Gugusdepan'] as $item) {
            $append('keuangan', $item, $expenseParent, null, 'expense');
        }
        $append('keuangan', 'Usaha Dana', null, 'Kekurangan rencana biaya diupayakan melalui usaha dana. Sumber usaha dana (jelaskan).', 'fundraising');

        return $entries;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array<string, mixed>>
     */
    public static function numbered(array $entries, string $section): array
    {
        $items = [];
        foreach ($entries as $index => $entry) {
            if (($entry['section'] ?? null) !== $section) {
                continue;
            }
            $items[] = [
                'index' => $index, 'entry' => $entry,
                'key' => (string) ($entry['key'] ?? 'legacy-'.$index),
            ];
        }

        $lookup = array_column($items, null, 'key');
        $counts = [];
        $result = [];
        foreach ($items as $item) {
            $parentKey = $item['entry']['parent_key'] ?? null;
            $parent = $parentKey && isset($lookup[$parentKey]) ? $lookup[$parentKey] : null;
            $depth = 0;
            if ($parent !== null) {
                $depth = isset($lookup[$parent['entry']['parent_key'] ?? '']) ? 2 : 1;
            }

            $group = $depth === 0 ? 'root' : (string) $parentKey;
            $counts[$group] = ($counts[$group] ?? 0) + 1;
            $position = $counts[$group];
            $marker = match (true) {
                $depth === 0 && $section === 'sarana' => $position.')',
                $depth === 0 && $section === 'keuangan' => $position.'.',
                $depth === 0 && in_array($section, ['siaga', 'penggalang', 'penegak', 'pandega'], true) => chr(96 + min($position, 26)).'.',
                $depth === 0 => $position.'.',
                $depth === 1 && $section === 'keuangan' => chr(96 + min($position, 26)).'.',
                $depth === 1 => $position.')',
                default => $position.')',
            };

            $result[] = $item + ['depth' => $depth, 'marker' => $marker];
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array{income: int, expense: int, shortfall: int, surplus: int}
     */
    public static function totals(array $entries): array
    {
        $income = 0;
        $expense = 0;
        foreach ($entries as $entry) {
            if (($entry['section'] ?? null) !== 'keuangan' || ($entry['currency'] ?? 'IDR') !== 'IDR') {
                continue;
            }
            $amount = max(0, (int) ($entry['amount'] ?? 0));
            if (($entry['finance_type'] ?? '') === 'income') {
                $income += $amount;
            } elseif (($entry['finance_type'] ?? '') === 'expense') {
                $expense += $amount;
            }
        }

        return ['income' => $income, 'expense' => $expense,
            'shortfall' => max(0, $expense - $income),
            'surplus' => max(0, $income - $expense)];
    }
}
