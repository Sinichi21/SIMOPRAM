<?php

namespace App\Console\Commands;

use App\Services\LetterNumberService;
use Illuminate\Console\Command;

class SetLetterSequence extends Command
{
    protected $signature = 'letters:set-sequence {school_id} {year} {last_number} {--direction=outgoing}';

    protected $description = 'Set nomor urut terakhir persuratan untuk sekolah dan tahun tertentu.';

    public function handle(LetterNumberService $service): int
    {
        $service->setLastNumber(
            (string) $this->option('direction'),
            (int) $this->argument('school_id'),
            (int) $this->argument('year'),
            (int) $this->argument('last_number'),
        );
        $this->info('Sequence persuratan berhasil diperbarui.');

        return self::SUCCESS;
    }
}
