<?php

namespace Tests\Feature\Letters;

use App\Models\LetterField;
use App\Models\LetterType;
use App\Models\School;
use App\Models\SchoolLetterSetting;
use App\Services\LetterNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterNumberFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_outgoing_number_uses_spreadsheet_pattern(): void
    {
        $school = School::factory()->create();
        SchoolLetterSetting::withoutGlobalScope('school')->create([
            'school_id' => $school->id,
            'gudep_code' => '04.007-008',
            'number_format' => '{sequence}/{type}/{month_roman}.{year_short}/{gudep}-{field}',
            'agenda_format' => '{sequence_padded}/SM/{year}',
        ]);
        $type = LetterType::withoutGlobalScope('school')->create(['school_id' => $school->id, 'code' => '02', 'name' => 'Surat Pemberitahuan']);
        $field = LetterField::withoutGlobalScope('school')->create(['school_id' => $school->id, 'code' => 'C', 'name' => 'Bidang Organisasi dan Kegiatan']);

        $number = app(LetterNumberService::class)->nextOutgoing($school->id, now()->setDate(2026, 6, 4), $type, $field);
        $this->assertSame('1/02/VI.26/04.007-008-C', $number);
    }
}
