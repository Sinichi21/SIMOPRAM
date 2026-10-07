<?php

namespace Tests\Feature\Letters;

use App\Models\LetterField;
use App\Models\LetterNumberSequence;
use App\Models\LetterType;
use App\Models\School;
use App\Models\SchoolDocumentSetting;
use App\Models\SchoolLetterSetting;
use App\Models\ScoutAdministrationProfile;
use App\Services\LetterNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoutAdministrationSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_administration_uses_its_own_gudep_and_sequence(): void
    {
        $school = School::factory()->create();

        SchoolLetterSetting::withoutGlobalScope('school')->create([
            'school_id' => $school->id,
            'gudep_code' => '03.061-062',
            'number_format' => '{sequence_padded}/{type}/{gudep}/{month_roman}/{year}',
            'agenda_format' => '{sequence_padded}/SM/{year}',
        ]);

        SchoolDocumentSetting::withoutGlobalScope('school')->forceCreate([
            'school_id' => $school->id,
            'gudep_male_number' => '03.061',
            'gudep_female_number' => '03.062',
        ]);

        foreach (['male', 'female', 'mabigus'] as $typeName) {
            ScoutAdministrationProfile::withoutGlobalScope('school')->create([
                'school_id' => $school->id,
                'type' => $typeName,
                'name' => $typeName,
                'number_format' => '{sequence_padded}/{type}/{gudep}/{month_roman}/{year}',
                'agenda_format' => '{sequence_padded}/SM/{year}',
                'is_active' => true,
            ]);
        }

        $type = LetterType::withoutGlobalScope('school')->create([
            'school_id' => $school->id,
            'code' => '04',
            'name' => 'Surat Tugas',
        ]);

        $field = LetterField::withoutGlobalScope('school')->create([
            'school_id' => $school->id,
            'code' => 'A',
            'name' => 'Pimpinan',
        ]);

        $service = app(LetterNumberService::class);
        $date = now()->setDate(2026, 9, 20);

        $this->assertSame('001/04/03.061/IX/2026', $service->nextOutgoing($school->id, $date, $type, $field, 'male'));
        $this->assertSame('001/04/03.062/IX/2026', $service->nextOutgoing($school->id, $date, $type, $field, 'female'));
        $this->assertSame('001/04/03.061-062/IX/2026', $service->nextOutgoing($school->id, $date, $type, $field, 'mabigus'));
        $this->assertSame('002/04/03.061/IX/2026', $service->nextOutgoing($school->id, $date, $type, $field, 'male'));

        $this->assertDatabaseHas('letter_number_sequences', [
            'school_id' => $school->id,
            'direction' => 'outgoing',
            'administration_type' => 'male',
            'year' => 2026,
            'last_number' => 2,
        ]);

        $this->assertSame(3, LetterNumberSequence::withoutGlobalScope('school')->where('school_id', $school->id)->count());
    }
}
