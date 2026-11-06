<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Livewire\Administration\GudepProgramPlanner;
use App\Models\GudepProgramSheet;
use App\Models\School;
use App\Support\GudepDocumentLetterhead;
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

        return view('administration.program-sheet-print', [
            'program' => $program,
            'gudep' => $program->scoutGroup,
            'year' => $program->academicYear,
            'sections' => GudepProgramPlanner::annualSections(),
            'letterhead' => GudepDocumentLetterhead::resolve(
                School::query()->findOrFail($schoolId),
                $program->scoutGroup,
                $program->kind === 'annual' ? 'mabigus' : ($program->letterhead_type ?: match ($program->gudepFormation?->gender) {
                    'putra' => 'male', 'putri' => 'female', default => 'mabigus',
                })
            ),
        ]);
    }
}
