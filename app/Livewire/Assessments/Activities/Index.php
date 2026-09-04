<?php

namespace App\Livewire\Assessments\Activities;

use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\AssessmentFactor;
use App\Models\ScoutLevel;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public ?int $activityId = null;

    public ?int $assessmentFactorId = null;

    public string $title = '';

    public string $mode = 'individual';

    public string $description = '';

    public string $search = '';

    public string $scoutLevelId = '';

    public function create(): void
    {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'activity_assessments.create'
                ),
            403
        );

        $validated =
            $this->validate([
                'activityId' => [
                    'required',
                    'integer',
                ],

                'assessmentFactorId' => [
                    'required',
                    'integer',
                ],

                'title' => [
                    'required',
                    'string',
                    'max:200',
                ],

                'mode' => [
                    'required',
                    Rule::in([
                        'individual',
                        'team',
                    ]),
                ],

                'description' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Tenant validation
        |--------------------------------------------------------------------------
        */

        Activity::query()
            ->findOrFail(
                $validated[
                    'activityId'
                ]
            );

        AssessmentFactor::query()
            ->where(
                'source_type',
                '!=',
                'attendance'
            )
            ->findOrFail(
                $validated[
                    'assessmentFactorId'
                ]
            );

        $assessment =
            ActivityAssessment::query()
                ->create([
                    'activity_id' => $validated[
                            'activityId'
                        ],

                    'assessment_factor_id' => $validated[
                            'assessmentFactorId'
                        ],

                    'title' => trim(
                        $validated[
                            'title'
                        ]
                    ),

                    'mode' => $validated[
                            'mode'
                        ],

                    'status' => 'draft',

                    'description' => trim(
                        $validated[
                            'description'
                        ] ?? ''
                    ) ?: null,

                    'created_by' => auth()->id(),
                ]);

        $this->reset([
            'activityId',
            'assessmentFactorId',
            'title',
            'description',
        ]);

        $this->mode =
            'individual';

        session()->flash(
            'status',
            'Form penilaian kegiatan berhasil dibuat.'
        );

        $this->redirectRoute(
            'activity-assessments.edit',
            [
                'assessment' => $assessment->id,
            ],
            navigate: true
        );
    }

    public function render()
    {
        $activities =
            Activity::query()
                ->with('scoutLevels')
                ->when(
                    $this->scoutLevelId,
                    function ($query): void {
                        $query->where(
                            function ($query): void {
                                $query
                                    ->whereDoesntHave('scoutLevels')
                                    ->orWhereHas(
                                        'scoutLevels',
                                        fn ($query) => $query->whereKey(
                                            (int) $this->scoutLevelId
                                        )
                                    );
                            }
                        );
                    }
                )
                ->orderByDesc(
                    'start_at'
                )
                ->get([
                    'id',
                    'title',
                    'start_at',
                ]);

        $factors =
            AssessmentFactor::query()
                ->where(
                    'source_type',
                    '!=',
                    'attendance'
                )
                ->orderBy(
                    'name'
                )
                ->get();

        $assessments =
            ActivityAssessment::query()
                ->with([
                    'activity',
                    'factor',
                    'activity.scoutLevels',
                ])
                ->when(
                    $this->scoutLevelId,
                    fn ($query) => $query->whereHas(
                        'activity',
                        function ($query): void {
                            $query->where(
                                function ($query): void {
                                    $query
                                        ->whereDoesntHave('scoutLevels')
                                        ->orWhereHas(
                                            'scoutLevels',
                                            fn ($query) => $query->whereKey(
                                                (int) $this->scoutLevelId
                                            )
                                        );
                                }
                            );
                        }
                    )
                )
                ->when(
                    trim(
                        $this->search
                    ) !== '',
                    function ($query): void {
                        $search =
                            '%'
                            .trim(
                                $this->search
                            )
                            .'%';

                        $query->where(
                            function ($query) use (
                                $search
                            ): void {
                                $query
                                    ->where(
                                        'title',
                                        'like',
                                        $search
                                    )
                                    ->orWhereHas(
                                        'activity',
                                        fn ($query) => $query->where(
                                            'title',
                                            'like',
                                            $search
                                        )
                                    );
                            }
                        );
                    }
                )
                ->latest()
                ->get();

        $scoutLevels = ScoutLevel::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'livewire.assessments.activities.index',
            [
                'activities' => $activities,

                'factors' => $factors,

                'assessments' => $assessments,

                'scoutLevels' => $scoutLevels,
            ]
        );
    }
}
