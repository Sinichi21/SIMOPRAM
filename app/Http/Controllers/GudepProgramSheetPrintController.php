<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Livewire\Administration\GudepProgramPlanner;
use App\Models\GudepProgramSheet;
use App\Models\School;
use App\Support\GudepDocumentLetterhead;
use App\Support\GudepProgramSignatories;
use App\Support\GudepWeeklyPersonnel;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GudepProgramSheetPrintController extends Controller
{
    public function __invoke(Request $request, int $sheet): View
    {
        abort_unless($request->user()?->can('gudep.view'), 403);

        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId !== null, 409, 'Pilih sekolah aktif terlebih dahulu.');

        $program = GudepProgramSheet::query()
            ->where('school_id', $schoolId)
            ->with(['scoutGroup', 'academicYear', 'semester', 'scoutLevel', 'gudepFormation', 'activity'])
            ->find($sheet);

        abort_unless($program !== null
            && $program->scoutGroup !== null
            && (int) $program->scoutGroup->school_id === (int) $schoolId
            && $program->academicYear !== null
            && (int) $program->academicYear->school_id === (int) $schoolId, 404);

        $weeklySignatures = null;
        if ($program->kind === 'weekly') {
            $options = GudepWeeklyPersonnel::candidates(
                $schoolId, $program->scout_group_id, $program->academic_year_id,
                $program->gudep_formation_id, $program->scheduled_for?->toDateString()
            );
            $weeklySignatures = GudepWeeklyPersonnel::snapshot($program, $options);
        }

        $school = School::query()->findOrFail($schoolId);
        $letterheadType = $program->letterhead_type ?: match ($program->gudepFormation?->gender) {
            'putra' => 'male', 'putri' => 'female', default => 'mabigus',
        };
        $letterhead = GudepDocumentLetterhead::resolve($school, $program->scoutGroup, $letterheadType);
        $signatures = GudepProgramSignatories::snapshot($program, GudepProgramSignatories::candidates(
            (int) $schoolId, $program->scout_group_id, $program->academic_year_id,
            $program->kind === 'annual' ? null : $program->gudep_formation_id,
            $program->scheduled_for?->toDateString(), $program->kind, $letterheadType
        ));

        return view('administration.program-sheet-print', [
            'program' => $program,
            'gudep' => $program->scoutGroup,
            'year' => $program->academicYear,
            'sections' => GudepProgramPlanner::annualSections(),
            'weeklySignatures' => $weeklySignatures,
            'programSignatures' => $signatures,
            'programCity' => $school->city ?: ($program->scoutGroup->kwarcab ?? ''),
            'programNumber' => match ($letterheadType) {
                'male' => $letterhead['male_number'],
                'female' => $letterhead['female_number'],
                default => trim($letterhead['male_number'].' - '.$letterhead['female_number']),
            },
            'letterhead' => $letterhead,
        ]);
    }
}
