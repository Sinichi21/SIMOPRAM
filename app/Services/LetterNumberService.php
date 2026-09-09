<?php

namespace App\Services;

use App\Models\LetterField;
use App\Models\LetterNumberSequence;
use App\Models\LetterType;
use App\Models\SchoolLetterSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class LetterNumberService
{
    public function nextOutgoing(int $schoolId, CarbonInterface $date, LetterType $type, LetterField $field): string
    {
        $settings = SchoolLetterSetting::withoutGlobalScope('school')->where('school_id', $schoolId)->firstOrFail();
        $number = $this->nextSequence('outgoing', $schoolId, (int) $date->year);

        return $this->render($settings->number_format, [
            'sequence' => (string) $number,
            'sequence_padded' => str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'type' => $type->code,
            'month' => str_pad((string) $date->month, 2, '0', STR_PAD_LEFT),
            'month_roman' => $this->romanMonth((int) $date->month),
            'year' => (string) $date->year,
            'year_short' => substr((string) $date->year, -2),
            'gudep' => (string) $settings->gudep_code,
            'field' => $field->code,
        ]);
    }

    public function nextAgenda(int $schoolId, CarbonInterface $date): string
    {
        $settings = SchoolLetterSetting::withoutGlobalScope('school')->where('school_id', $schoolId)->firstOrFail();
        $number = $this->nextSequence('incoming', $schoolId, (int) $date->year);

        return $this->render($settings->agenda_format, [
            'sequence' => (string) $number,
            'sequence_padded' => str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'year' => (string) $date->year,
            'year_short' => substr((string) $date->year, -2),
            'month' => str_pad((string) $date->month, 2, '0', STR_PAD_LEFT),
            'month_roman' => $this->romanMonth((int) $date->month),
        ]);
    }

    /** Backward compatibility with Persuratan MVP. */
    public function next(string $direction, int $schoolId, int $year, int $month): string
    {
        $date = now()->setDate($year, $month, 1);
        if ($direction === 'incoming') {
            return $this->nextAgenda($schoolId, $date);
        }
        throw new \InvalidArgumentException('Surat keluar v2 memerlukan LetterType dan LetterField. Gunakan nextOutgoing().');
    }

    public function setLastNumber(string $direction, int $schoolId, int $year, int $lastNumber): void
    {
        LetterNumberSequence::withoutGlobalScope('school')->updateOrCreate(
            ['school_id' => $schoolId, 'direction' => $direction, 'year' => $year],
            ['last_number' => max(0, $lastNumber)]
        );
    }

    private function nextSequence(string $direction, int $schoolId, int $year): int
    {
        return DB::transaction(function () use ($direction, $schoolId, $year): int {
            $sequence = LetterNumberSequence::withoutGlobalScope('school')
                ->where('school_id', $schoolId)->where('direction', $direction)->where('year', $year)
                ->lockForUpdate()->first();

            if (! $sequence) {
                $sequence = LetterNumberSequence::withoutGlobalScope('school')->create([
                    'school_id' => $schoolId, 'direction' => $direction, 'year' => $year, 'last_number' => 0,
                ]);
            }

            $sequence->increment('last_number');

            return (int) $sequence->fresh()->last_number;
        }, 3);
    }

    private function render(string $format, array $tokens): string
    {
        foreach ($tokens as $key => $value) {
            $format = str_replace('{'.$key.'}', $value, $format);
        }

        return trim($format);
    }

    private function romanMonth(int $month): string
    {
        return [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'][$month] ?? (string) $month;
    }
}
