<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Semester;
use App\Support\GudepProgramSemesterProjection;
use PHPUnit\Framework\TestCase;

class GudepProgramSemesterProjectionTest extends TestCase
{
    public function test_ganjil_uses_august_through_december_only(): void
    {
        $semester = new Semester(['semester_number' => 1]);
        $this->assertSame([8, 9, 10, 11, 12], GudepProgramSemesterProjection::months($semester));
        $this->assertSame(['Agu', 'Sep', 'Okt', 'Nov', 'Des'], GudepProgramSemesterProjection::monthLabels($semester));
        $this->assertSame([true, false, true, false, true], GudepProgramSemesterProjection::fromAnnual(
            [true, false, true, false, true, true, false, false, false, false, false, false], $semester
        ));
    }

    public function test_genap_uses_january_through_july_without_month_shifting(): void
    {
        $semester = new Semester(['semester_number' => 2]);
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], GudepProgramSemesterProjection::months($semester));
        $this->assertSame(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'], GudepProgramSemesterProjection::monthLabels($semester));
        $this->assertSame([true, false, true, false, false, true, false], GudepProgramSemesterProjection::fromAnnual(
            [false, false, false, false, false, true, false, true, false, false, true, false], $semester
        ));
    }

    public function test_row_sync_preserves_semester_notes_and_participants(): void
    {
        $row = [
            'source_key' => 'annual-a', 'description' => 'Old text', 'months' => [false, false, false, false, false],
            'participants' => 30, 'notes' => 'Dibawa Pratama',
        ];
        $annual = [[
            'key' => 'annual-a', 'section' => 'penggalang',
            'description' => 'Latihan Ramu', 'months' => [true, true, false, false, false, false, false, false, false, false, false, false],
        ]];
        $result = GudepProgramSemesterProjection::synchronizeRow($row, $annual, new Semester(['semester_number' => 1]));
        $this->assertSame('Latihan Ramu', $result['description']);
        $this->assertSame([true, true, false, false, false], $result['months']);
        $this->assertSame(30, $result['participants']);
        $this->assertSame('Dibawa Pratama', $result['notes']);
        $this->assertNull(GudepProgramSemesterProjection::synchronizeRow([
            'source_key' => 'missing',
        ], $annual, new Semester(['semester_number' => 1])));
    }
}
