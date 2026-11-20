<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\GudepProgramSheet;
use App\Models\Journal;
use App\Support\GudepProgramProgress;
use PHPUnit\Framework\TestCase;

class GudepProgramProgressTest extends TestCase
{
    public function test_only_completed_activities_with_journals_count_as_documented_execution(): void
    {
        $annual = new GudepProgramSheet(['kind' => 'annual']);
        $semester = new GudepProgramSheet(['kind' => 'semester']);

        $unlinked = new GudepProgramSheet(['kind' => 'weekly']);
        $unlinked->setRelation('activity', null);

        $scheduled = new Activity(['status' => 'planned']);
        $scheduled->setRelation('journal', null);
        $linked = new GudepProgramSheet(['kind' => 'weekly']);
        $linked->setRelation('activity', $scheduled);

        $completed = new Activity(['status' => 'completed']);
        $completed->setRelation('journal', null);
        $finished = new GudepProgramSheet(['kind' => 'weekly']);
        $finished->setRelation('activity', $completed);

        $documented = new Activity(['status' => 'completed']);
        $documented->setRelation('journal', new Journal);
        $evidenced = new GudepProgramSheet(['kind' => 'weekly']);
        $evidenced->setRelation('activity', $documented);

        $this->assertSame([
            'annual' => 1,
            'semester' => 1,
            'weekly' => 4,
            'linked' => 3,
            'completed' => 2,
            'evidenced' => 1,
        ], GudepProgramProgress::summary(collect([
            $annual, $semester, $unlinked, $linked, $finished, $evidenced,
        ])));

        $this->assertSame('Belum ditautkan', GudepProgramProgress::evidenceLabel($unlinked));
        $this->assertSame('Agenda terhubung', GudepProgramProgress::evidenceLabel($linked));
        $this->assertSame('Selesai · jurnal belum ada', GudepProgramProgress::evidenceLabel($finished));
        $this->assertSame('Selesai · jurnal tersedia', GudepProgramProgress::evidenceLabel($evidenced));
    }
}
